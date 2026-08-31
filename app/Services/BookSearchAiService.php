<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * แปลงประโยคค้นหาภาษาธรรมชาติเป็นตัวกรองของหน้าหลัก ด้วย Gemini
 *
 * ใช้ pattern เดียวกับ BookConditionService (key/model/endpoint/การจัดการ error)
 * ต่างกันตรงที่อันนี้ค้างอยู่กลาง page load เลยตั้ง timeout สั้นกว่า
 * และไม่ว่าพังตรงไหนก็ต้องคืน success=false เสมอ ห้าม throw ออกไปให้หน้าเว็บพัง
 */
class BookSearchAiService
{
    /** วินาทีที่ยอมรอ Gemini ก่อนถอยไปค้นแบบปกติ */
    private const TIMEOUT = 10;

    /** เก็บผลตีความไว้กันยิง API ซ้ำตอนกดหน้า 2/3 หรือค้นประโยคเดิม (วินาที) */
    private const CACHE_TTL = 3600;

    /** ความยาวสูงสุดของ keyword ที่ยอมรับจาก AI */
    private const KEYWORD_MAX = 100;

    /**
     * @return array{success: bool, filters?: array, error?: string, cached?: bool}
     */
    public function interpret(string $sentence): array
    {
        $sentence = trim($sentence);

        if ($sentence === '') {
            return ['success' => false, 'error' => 'ยังไม่ได้พิมพ์สิ่งที่อยากค้นหา'];
        }

        // ประโยคเดิม (ไม่สนตัวพิมพ์เล็กใหญ่/ช่องว่างเกิน) ใช้ผลเดิมได้เลย ไม่ต้องเสียโควต้า
        $cacheKey = 'ai_search:' . md5(mb_strtolower(preg_replace('/\s+/u', ' ', $sentence)));

        if (($cached = Cache::get($cacheKey)) !== null) {
            return ['success' => true, 'filters' => $cached, 'cached' => true];
        }

        $result = $this->askGemini($sentence);

        if ($result['success']) {
            Cache::put($cacheKey, $result['filters'], self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * @return array{success: bool, filters?: array, error?: string}
     */
    private function askGemini(string $sentence): array
    {
        try {
            $apiKey = config('services.gemini.key');

            if (! $apiKey) {
                return ['success' => false, 'error' => 'ยังไม่ได้ตั้งค่า GEMINI_API_KEY'];
            }

            $model = config('services.gemini.model', 'gemini-2.5-flash');

            $response = Http::timeout(self::TIMEOUT)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $this->buildPrompt($sentence)]]]],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        // ตีความ filter ต้องการความคงเส้นคงวา ไม่ใช่ความสร้างสรรค์
                        'temperature' => 0,
                    ],
                ]
            );

            if ($response->status() === 429) {
                return ['success' => false, 'error' => 'ตอนนี้ AI มีคิวใช้งานเยอะ'];
            }

            if (! $response->successful()) {
                Log::warning('Gemini AI search HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'เรียก AI ไม่สำเร็จ (รหัส ' . $response->status() . ')'];
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $data = is_string($text) ? json_decode($text, true) : null;

            if (! is_array($data)) {
                Log::warning('Gemini AI search returned non-JSON', ['raw' => $text]);

                return ['success' => false, 'error' => 'AI ตอบกลับข้อมูลไม่ถูกต้อง'];
            }

            $filters = $this->sanitize($data);

            // ตีความแล้วไม่ได้อะไรเลยสักช่อง = ช่วยอะไรไม่ได้ ถอยไปค้นแบบปกติดีกว่า
            if (collect($filters)->every(fn ($v) => $v === null)) {
                return ['success' => false, 'error' => 'AI ตีความประโยคนี้ไม่ได้'];
            }

            return ['success' => true, 'filters' => $filters];
        } catch (\Throwable $e) {
            Log::warning('Gemini AI search failed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'เชื่อมต่อ AI ไม่สำเร็จ'];
        }
    }

    /**
     * ตรวจค่าที่ AI ตอบมาก่อนเอาไปยิง query — ไม่เชื่อ AI ตรงๆ สักช่อง
     * ช่องไหนไม่ผ่านให้เป็น null (ข้ามไป) ไม่ใช่เอาค่ามั่วไป query
     *
     * @return array{keyword: ?string, category: ?string, price_min: ?float, price_max: ?float, type: ?string}
     */
    private function sanitize(array $data): array
    {
        // keyword: ต้องเป็นข้อความ ตัดความยาวกันคนหลอกให้ AI พ่นอะไรยาวๆ ออกมา
        $keyword = null;
        if (isset($data['keyword']) && is_string($data['keyword'])) {
            $trimmed = trim($data['keyword']);
            if ($trimmed !== '') {
                $keyword = mb_substr($trimmed, 0, self::KEYWORD_MAX);
            }
        }

        // category: ต้องตรงกับหมวดที่มีจริงในระบบเท่านั้น AI แต่งหมวดใหม่มาก็ทิ้ง
        $category = null;
        if (isset($data['category']) && is_string($data['category'])
            && in_array($data['category'], Book::CATEGORIES, true)) {
            $category = $data['category'];
        }

        // type: enum ในตารางมีแค่ 2 ค่า
        $type = null;
        if (isset($data['type']) && in_array($data['type'], ['sale', 'exchange'], true)) {
            $type = $data['type'];
        }

        $min = $this->numeric($data['price_min'] ?? null);
        $max = $this->numeric($data['price_max'] ?? null);

        // AI สลับหัวท้ายมา ("200 ถึง 100") ให้เรียงให้ถูกแทนที่จะทิ้งทั้งคู่
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [
            'keyword' => $keyword,
            'category' => $category,
            'price_min' => $min,
            'price_max' => $max,
            'type' => $type,
        ];
    }

    /** ราคาต้องเป็นตัวเลขไม่ติดลบเท่านั้น (is_numeric กัน "ประมาณ 200" / true / array) */
    private function numeric($value): ?float
    {
        if ($value === null || is_bool($value) || ! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number >= 0 ? $number : null;
    }

    private function buildPrompt(string $sentence): string
    {
        $categories = implode(', ', Book::CATEGORIES);

        // ใส่ประโยคผู้ใช้เป็น JSON string กัน quote/บรรทัดใหม่ไปทำ prompt เพี้ยน
        $userInput = json_encode($sentence, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
        คุณเป็นตัวช่วยแปลงประโยคค้นหาภาษาธรรมชาติของผู้ใช้ ให้เป็นตัวกรองสำหรับเว็บตลาดซื้อขายแลกเปลี่ยนหนังสือมือสอง

        ตอบเป็น JSON เท่านั้น ห้ามมีข้อความอื่นนอกเหนือจาก JSON ตามโครงสร้างนี้เป๊ะๆ:
        {"keyword": string|null, "category": string|null, "price_min": number|null, "price_max": number|null, "type": "sale"|"exchange"|null}

        กติกา:
        - category ต้องเป็นค่าใดค่าหนึ่งจากรายการนี้เท่านั้น: {$categories}
          ถ้าประโยคไม่ได้บอกหมวด หรือบอกหมวดที่ไม่อยู่ในรายการนี้ ให้เป็น null (ห้ามสร้างหมวดใหม่เอง)
        - type: "sale" = ผู้ใช้ต้องการซื้อ, "exchange" = ต้องการแลกเปลี่ยน ถ้าไม่ได้ระบุชัดให้เป็น null
        - price_min / price_max เป็นตัวเลขจำนวนเงินบาท (ตัวเลขล้วน ไม่ต้องมีหน่วย)
          ตัวอย่าง: "ไม่เกิน 200" -> price_max: 200 | "เริ่มต้น 100" -> price_min: 100 | "ราคา 100-300" -> price_min: 100, price_max: 300
        - keyword = คำสำคัญเกี่ยวกับชื่อเรื่องหรือเนื้อหาที่เหลือ เช่น "สืบสวน", "แฮร์รี่ พอตเตอร์"
          ห้ามใส่คำที่เป็นหมวดหมู่ ราคา หรือประเภทซื้อ/แลก ลงใน keyword ซ้ำอีก ถ้าไม่เหลืออะไรให้เป็น null
        - ห้ามเดาค่าที่ประโยคไม่ได้บอก ช่องไหนไม่แน่ใจให้ใส่ null

        ประโยคของผู้ใช้: {$userInput}
        PROMPT;
    }
}

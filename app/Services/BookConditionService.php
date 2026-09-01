<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookConditionService
{
    private const SLOT_LABELS = [
        'cover' => 'ปกหนังสือ',
        'spine' => 'สันหนังสือ',
        'page' => 'หน้าหนังสือ (สุ่ม)',
        'back' => 'ปกหลังหนังสือ',
    ];

    /**
     * วิเคราะห์สภาพหนังสือจากรูปหลายมุมด้วย Gemini (ยิง API ครั้งเดียวต่อการเรียก 1 ครั้ง)
     *
     * @param array<string, UploadedFile> $slotFiles เช่น ['cover' => $file, 'spine' => $file, ...] เฉพาะมุมที่มีรูปจริง
     * @return array{success: bool, condition?: string, score?: int, overall_note?: string, per_image?: array, error?: string}
     */
    public function analyze(array $slotFiles): array
    {
        try {
            $apiKey = config('services.gemini.key');

            if (! $apiKey) {
                return ['success' => false, 'error' => 'ยังไม่ได้ตั้งค่า GEMINI_API_KEY'];
            }

            if (empty($slotFiles)) {
                return ['success' => false, 'error' => 'ไม่มีรูปให้ประเมิน'];
            }

            $parts = [['text' => $this->buildPrompt()]];

            foreach ($slotFiles as $slot => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $label = self::SLOT_LABELS[$slot] ?? $slot;
                $parts[] = ['text' => "รูปสำหรับมุม: {$slot} ({$label})"];
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $file->getMimeType(),
                        'data' => base64_encode(file_get_contents($file->getRealPath())),
                    ],
                ];
            }

            $model = config('services.gemini.model', 'gemini-3.5-flash');

            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => $parts]],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                    ],
                ]
            );

            if ($response->status() === 429) {
                return ['success' => false, 'error' => 'ใช้งานเกินโควต้าฟรีของ Gemini ในขณะนี้ กรุณาลองใหม่ภายหลัง'];
            }

            if (! $response->successful()) {
                Log::warning('Gemini book condition analysis HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => false, 'error' => 'เรียก AI ไม่สำเร็จ (รหัส ' . $response->status() . ')'];
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $data = is_string($text) ? json_decode($text, true) : null;

            if (! is_array($data) || ! isset($data['overall']['score'], $data['overall']['condition'], $data['per_image']) || ! is_array($data['per_image'])) {
                Log::warning('Gemini book condition analysis returned unexpected shape', ['raw' => $text]);

                return ['success' => false, 'error' => 'AI ตอบกลับข้อมูลไม่ถูกต้อง'];
            }

            return [
                'success' => true,
                'condition' => (string) $data['overall']['condition'],
                'score' => (int) round($data['overall']['score']),
                'overall_note' => (string) ($data['overall']['note'] ?? ''),
                'per_image' => $data['per_image'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Gemini book condition analysis failed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ AI กรุณาลองใหม่'];
        }
    }

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
คุณเป็นผู้เชี่ยวชาญประเมินสภาพหนังสือมือสองสำหรับตลาดซื้อขายออนไลน์ ผู้ใช้จะส่งรูปภาพหนังสือมาไม่เกิน 4 รูป แต่ละรูปมีป้ายกำกับมุมถ่ายกำกับไว้ ดังนี้:
- cover: ปกหน้าของหนังสือ
- spine: สันหนังสือ (ด้านข้างที่เห็นชื่อเรื่องตอนวางเรียงบนชั้น)
- page: หน้าในเล่ม (สุ่มเปิดหน้าใดก็ได้ เพื่อดูสภาพกระดาษ/รอยเปื้อน/รอยขีดเขียน)
- back: ปกหลังของหนังสือ

งานของคุณมี 2 ส่วน:

### ส่วนที่ 1: ตรวจสอบว่ารูปตรงกับป้ายกำกับมุมหรือไม่ (angle_match)
สำหรับแต่ละรูปที่ได้รับ ให้ตรวจสอบว่าเนื้อหาของรูปตรงกับมุมที่ระบุจริงหรือไม่ เช่น รูปที่ติดป้าย "spine" ควรเป็นภาพสันหนังสือแนวตั้งบางๆ ไม่ใช่ภาพปกหน้าเต็มเล่ม ถ้ารูปดูไม่ตรงกับมุมที่ระบุ ให้ตั้ง angle_match เป็น false มิเช่นนั้นเป็น true (ถ้าไม่แน่ใจแต่พอเป็นไปได้ ให้ถือว่า true เพื่อไม่ให้เตือนเกินจำเป็น)

### ส่วนที่ 2: ประเมินสภาพหนังสือจากรูปที่ได้รับ
ให้คะแนนสภาพหนังสือแต่ละรูป (0-100) และระบุระดับสภาพ ตามเกณฑ์นี้:
- ดีมาก (85-100): แทบไม่มีร่องรอยการใช้งาน ปก/สันไม่มีรอยขีดข่วน มุมไม่บุบ กระดาษสะอาดไม่มีรอยเปื้อนหรือรอยพับ เหมือนใหม่
- ดี (70-84): มีร่องรอยการใช้งานเล็กน้อย เช่น มุมมนเล็กน้อย รอยขีดข่วนจางๆ ที่ปก แต่ไม่มีรอยฉีกขาด กระดาษด้านในยังสะอาดดี
- พอใช้ (50-69): มีร่องรอยการใช้งานชัดเจน เช่น ปกยับ/มีรอยขีดข่วนหลายจุด สันหนังสือมีรอยแตกลายงา กระดาษอาจมีรอยเปื้อนหรือรอยขีดเขียนบ้าง แต่ยังอ่านได้ปกติ ไม่มีหน้าขาดหาย
- ต้องซ่อม (30-49): ชำรุดชัดเจน เช่น ปกฉีกขาดหรือหลุดออกจากเล่ม สันหนังสือแตกจนหน้ากระดาษหลุดร่วง กระดาษเปื้อนหนักหรือมีหน้าขาดหาย

ให้คำอธิบายสั้นๆ (note) เป็นภาษาไทยสำหรับแต่ละรูป ระบุตำหนิที่เห็นจริงจากรูปนั้น (เช่น "มุมขวาบนยับเล็กน้อย มีรอยขีดข่วนจางที่ปก") ถ้ารูปนั้นดูสภาพดีไม่มีตำหนิ ให้เขียนว่า "ไม่พบตำหนิที่เห็นได้ชัดจากรูปนี้"

ประเมินเฉพาะรูปที่ได้รับมาจริงเท่านั้น (ไม่ต้องประเมินมุมที่ไม่มีรูปส่งมา) แล้วสรุปภาพรวม (overall) จากค่าเฉลี่ยของรูปที่ประเมินทั้งหมด พร้อมคำสรุปสั้นๆ 1-2 ประโยค

### รูปแบบคำตอบ
ตอบเป็น JSON เท่านั้น ห้ามมีข้อความอื่นนอกเหนือจาก JSON ตามโครงสร้างนี้เป๊ะๆ:

{
  "per_image": {
    "<slot>": {
      "angle_match": true หรือ false,
      "score": ตัวเลข 0-100,
      "condition": "ดีมาก" หรือ "ดี" หรือ "พอใช้" หรือ "ต้องซ่อม",
      "note": "คำอธิบายภาษาไทย"
    }
  },
  "overall": {
    "score": ตัวเลข 0-100 (ค่าเฉลี่ยจากรูปที่ประเมิน ปัดเป็นจำนวนเต็ม),
    "condition": "ดีมาก" หรือ "ดี" หรือ "พอใช้" หรือ "ต้องซ่อม",
    "note": "คำสรุปภาพรวม 1-2 ประโยค"
  }
}

โดย <slot> ใน per_image ให้ใช้เฉพาะ key ของรูปที่ได้รับมาจริงเท่านั้น (cover, spine, page, back ตามที่มี) ห้ามใส่ key ของมุมที่ไม่มีรูปส่งมา
PROMPT;
    }
}

<?php

namespace App\Services;

class BookConditionService
{
    /**
     * วิเคราะห์สภาพหนังสือจากรูปภาพ
     * (ตอนนี้เป็นแบบสุ่ม ภายหลังเปลี่ยนเป็น Claude API จริงได้)
     */
    public function analyze(string $imagePath): array
    {
        // รายการสภาพที่เป็นไปได้ พร้อมช่วงคะแนน
        $conditions = [
            ['condition' => 'ดีมาก', 'min' => 85, 'max' => 100],
            ['condition' => 'ดี', 'min' => 70, 'max' => 84],
            ['condition' => 'พอใช้', 'min' => 50, 'max' => 69],
            ['condition' => 'ต้องซ่อม', 'min' => 30, 'max' => 49],
        ];

        // สุ่มเลือกสภาพ
        $selected = $conditions[array_rand($conditions)];
        $score = rand($selected['min'], $selected['max']);

        return [
            'condition' => $selected['condition'],
            'score' => $score,
        ];
    }
}
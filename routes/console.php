<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ล้างแชทในถังขยะที่ครบกำหนด ถ้าเครื่องไม่ได้ตั้ง scheduler ไว้ก็ยังทำงานได้
// เพราะ ChatController::index() เรียกล้างของ user คนนั้นทุกครั้งที่เปิดหน้ารายการแชท
Schedule::command('chat:purge-trash')->dailyAt('03:00');

<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChatController;
use App\Models\ConversationUserState;
use Illuminate\Console\Command;

class PurgeChatTrash extends Command
{
    protected $signature = 'chat:purge-trash';

    protected $description = 'ลบแชทในถังขยะที่ครบ ' . ConversationUserState::TRASH_DAYS . ' วันแล้วอย่างถาวร';

    public function handle(): int
    {
        $purged = ChatController::purgeExpiredTrash();

        $this->info("ลบถาวรแล้ว {$purged} รายการ");

        return self::SUCCESS;
    }
}

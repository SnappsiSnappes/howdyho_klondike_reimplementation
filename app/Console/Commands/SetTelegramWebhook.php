<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {bot?}';
    protected $description = 'Установить вебхук для Telegram бота';

    public function handle()
    {
        $botName = $this->argument('bot') ?? config('telegram.default');
        $telegram = Telegram::bot($botName);
        
        $response = $telegram->setWebhook([
            'url' => config("telegram.bots.{$botName}.webhook_url"),
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]);
        
        if ($response) {
            $this->info("✅ Вебхук установлен для бота: {$botName}");
        } else {
            $this->error("❌ Ошибка: " . $response->getDescription());
        }
        
        return Command::SUCCESS;
    }
}
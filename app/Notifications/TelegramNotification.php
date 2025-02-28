<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        return TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->content("*{$this->message['symbol']} - Tín hiệu Mua*\n\n".
                "💰 Giá mua: ".number_format($this->message['entry_price'], 0, ',', ',')." VNĐ\n".
                "🎯 Take Profit: ".number_format($this->message['take_profit'], 0, ',', ',')." VNĐ (".number_format($this->message['tp_percentage'], 1)."%) \n".
                "🛑 Stop Loss: ".number_format($this->message['stop_loss'], 0, ',', ',')." VNĐ (".number_format($this->message['sl_percentage'], 1)."%) \n\n".
                "📊 Khối lượng: ".number_format($this->message['volume'], 0, ',', ',')." cổ phiếu\n".
                "💵 Giá trị: ".number_format($this->message['value'], 0, ',', ',')." VNĐ\n\n".
                "⏳ Thời gian kỳ vọng: ".$this->message['duration']." phiên giao dịch");
    }

}

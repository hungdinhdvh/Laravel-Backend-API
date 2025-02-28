<?php

namespace App\Http\Controllers;

use App\Notifications\TelegramNotification;
use Illuminate\Http\Request;

/**
 * @group Telegram Notifications
 *
 * APIs for sending Telegram notifications
 */
class TelegramNotificationController extends Controller
{
    /**
     * Send Trading Signal
     * @bodyParam symbol string required Mã cổ phiếu. Example: AAA
     * @bodyParam entry_price numeric required Giá mua vào (VNĐ). Example: 8680
     * @bodyParam take_profit numeric required Giá chốt lời (VNĐ). Example: 8845
     * @bodyParam stop_loss numeric required Giá cắt lỗ (VNĐ). Example: 8570
     * @bodyParam volume numeric required Khối lượng giao dịch (cổ phiếu). Example: 1803000
     * @bodyParam duration string required Thời gian kỳ vọng (phiên). Example: 3 - 5
     * @response {
     *   "message": "Notification sent successfully",
     *   "preview": "🔥 MUA cổ phiếu AAA giá 8,680 VNĐ\n🎯 Take Profit: 8,845 VNĐ (+1.9%)\n🛑 Stop-loss: 8,570 VNĐ (-1.3%)\n📊 Khối lượng giao dịch: 1,803,000 cổ phiếu\n💰 Tổng giá trị giao dịch: 15,713,000,000 VNĐ\n⏳ Thời gian kỳ vọng: 3 - 5 phiên giao dịch"
     * }
     */
    public function send(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string|max:10',
            'entry_price' => 'required|numeric|min:0',
            'take_profit' => 'required|numeric|min:0',
            'stop_loss' => 'required|numeric|min:0',
            'volume' => 'required|numeric|min:0',
            'duration' => 'required|string',
        ]);

        $data = $request->only([
            'symbol',
            'entry_price',
            'take_profit',
            'stop_loss',
            'volume',
            'duration'
        ]);

        // Calculate percentages and total value
        $data['tp_percentage'] = (($data['take_profit'] - $data['entry_price']) / $data['entry_price']) * 100;
        $data['sl_percentage'] = (($data['stop_loss'] - $data['entry_price']) / $data['entry_price']) * 100;
        $data['value'] = $data['entry_price'] * $data['volume'];

        $user = auth()->user();
        $user->notify(new TelegramNotification($data));

        return response()->json(['message' => 'Notification sent successfully']);
    }
}

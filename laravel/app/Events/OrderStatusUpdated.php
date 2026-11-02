<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderStatusUpdated implements ShouldBroadcast
{
    public int $orderId;
    protected ?int $customerId;

    public function __construct(Order $order)
    {
        $this->orderId = (int) $order->id;
        $this->customerId = $order->user_id === null ? null : (int) $order->user_id;
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.orders')];

        if ($this->customerId !== null) {
            $channels[] = new PrivateChannel('customer.orders.' . $this->customerId);
        }

        return $channels;
    }

    public function broadcastQueue(): string
    {
        return 'order-status';
    }

    public function broadcastWith(): array
    {
        // Giao diện nhận ID rồi lấy trạng thái mới nhất qua endpoint có phân quyền.
        return ['order_id' => $this->orderId];
    }
}

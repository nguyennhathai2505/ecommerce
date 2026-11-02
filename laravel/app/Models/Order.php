<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    //

    public const STATUSES = [
        'pending' => ['label' => 'Chờ xác nhận', 'color' => 'orange', 'classes' => 'border-orange-200 bg-orange-50 text-orange-700'],
        'processing' => ['label' => 'Đã nhận đơn', 'color' => 'blue', 'classes' => 'border-blue-200 bg-blue-50 text-blue-700'],
        'delivered' => ['label' => 'Đã giao hàng', 'color' => 'green', 'classes' => 'border-green-200 bg-green-50 text-green-700'],
        'cancelled' => ['label' => 'Đã hủy', 'color' => 'red', 'classes' => 'border-red-200 bg-red-50 text-red-700'],
    ];

    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id', 'order_code', 'customer_name', 'customer_phone', 'shipping_address', 'note', 'total_amount', 'shipping_fee',
        'discount_amount', 'payment_method', 'status'
    ];

    protected $casts = [
        'total_amount'=>'decimal:2', 
        'shipping_fee'=>'decimal:2',
        'discount_amount'=>'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items():HasMany
    {
        return $this->hasMany(OrderItem::class);

    }

    public function getStatusLabelAttribute(){
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }


    public function getStatusColorAttribute()
    {
        return self::STATUSES[$this->status]['color'] ?? 'gray';
    }

    public function allowedNextStatuses(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function statusData(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'label' => $this->status_label,
            'classes' => self::STATUSES[$this->status]['classes'] ?? 'border-gray-200 bg-gray-50 text-gray-700',
            'allowed_statuses' => $this->allowedNextStatuses(),
            'can_cancel' => $this->status === 'pending',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'branch_id', 'cashier_id', 'customer_id',
        'status', 'total', 'delivery_notes', 'assigned_at', 'completed_at', 'served_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'served_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderNumber(): string
    {
        $last = static::latest('id')->first();
        $number = $last ? $last->id + 1 : 1;
        return 'ORD-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'whatsapp'];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public static function findOrCreateFromData(array $data): self
    {
        $phone = $data['phone'] ?? null;
        if ($phone) {
            return static::firstOrCreate(['phone' => $phone], $data);
        }
        return static::create($data);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BinanceApiKey extends Model
{
    protected $fillable = [
        'api_key',
        'api_secret',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Encrypt API secret before saving
    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = Crypt::encryptString($value);
    }

    // Decrypt API secret when accessing
    public function getApiSecretAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}

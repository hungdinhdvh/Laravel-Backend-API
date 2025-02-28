<?php

namespace App\Repositories;

use App\Models\BinanceApiKey;
use Illuminate\Support\Facades\Auth;

class BinanceRepository
{
    protected $model;

    public function __construct(BinanceApiKey $model)
    {
        $this->model = $model;
    }

    /**
     * Create new API key record
     */
    public function create(array $data)
    {
        return $this->model->create([
            'user_id' => $data['user_id'],
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'],
            'is_active' => true
        ]);
    }

    /**
     * Update API key record
     */
    public function update(BinanceApiKey $apiKey, array $data)
    {
        return $apiKey->update([
            'api_key' => $data['api_key'],
            'api_secret' => $data['api_secret'],
            'last_used_at' => now()
        ]);
    }

    /**
     * Get active API key for current user
     */
    public function getActiveKey()
    {
        return $this->model
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();
    }

    /**
     * Deactivate all user's API keys
     */
    public function deactivateAllUserKeys($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->update(['is_active' => false]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(BinanceApiKey $apiKey)
    {
        return $apiKey->update(['last_used_at' => now()]);
    }

    /**
     * Delete API key
     */
    public function delete(BinanceApiKey $apiKey)
    {
        return $apiKey->delete();
    }
}

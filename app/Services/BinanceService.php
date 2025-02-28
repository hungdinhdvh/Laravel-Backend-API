<?php

namespace App\Services;

use App\Models\BinanceApiKey;
use App\Repositories\BinanceRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    protected $repository;
    protected $baseUrl = 'https://api.binance.com';

    public function __construct(BinanceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Store new API credentials
     */
    public function storeApiKeys(array $data, int $userId)
    {
        try {
            // Validate new credentials
            if (!$this->validateApiCredentials($data['api_key'], $data['api_secret'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid API credentials'
                ];
            }

            // Deactivate existing keys
            $this->repository->deactivateAllUserKeys($userId);

            // Create new API key
            $data['user_id'] = $userId;
            $apiKey = $this->repository->create($data);

            return [
                'success' => true,
                'message' => 'API credentials stored successfully',
                'data' => [
                    'id' => $apiKey->id,
                    'api_key' => $apiKey->api_key,
                    'created_at' => $apiKey->created_at
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to store API credentials'
            ];
        }
    }

    /**
     * Update API credentials
     */
    public function updateApiKeys(BinanceApiKey $apiKey, array $data)
    {
        try {
            // Validate new credentials
            if (!$this->validateApiCredentials($data['api_key'], $data['api_secret'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid API credentials'
                ];
            }

            $this->repository->update($apiKey, $data);

            return [
                'success' => true,
                'message' => 'API credentials updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update API credentials'
            ];
        }
    }

    /**
     * Check API key status
     */
    public function checkApiKeyStatus(BinanceApiKey $apiKey)
    {
        try {
            $isValid = $this->validateApiCredentials($apiKey->api_key, $apiKey->api_secret);

            if ($isValid) {
                $this->repository->updateLastUsed($apiKey);
            }

            return [
                'success' => true,
                'message' => $isValid ? 'API key is valid' : 'API key is invalid',
                'data' => [
                    'is_valid' => $isValid,
                    'last_used_at' => $apiKey->last_used_at,
                    'is_active' => $apiKey->is_active
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to check API key status'
            ];
        }
    }

    /**
     * Delete API key
     */
    public function deleteApiKey(BinanceApiKey $apiKey)
    {
        try {
            $this->repository->delete($apiKey);

            return [
                'success' => true,
                'message' => 'API key deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete API key'
            ];
        }
    }

    /**
     * Test connection with Binance API
     */
    public function testConnection(BinanceApiKey $apiKey): array
    {
        try {
            $client = new BinanceClient($apiKey->api_key, $apiKey->api_secret);
            $result = $client->testConnection();

            if ($result['success']) {
                $this->repository->updateLastUsed($apiKey);
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => $result['data']
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error']
            ];

        } catch (\Exception $e) {
            Log::error('Binance connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate API credentials before saving
     */
    protected function validateApiCredentials(string $apiKey, string $apiSecret): bool
    {
        $client = new BinanceClient($apiKey, $apiSecret);
        $result = $client->testConnection();
        return $result['success'];
    }
}

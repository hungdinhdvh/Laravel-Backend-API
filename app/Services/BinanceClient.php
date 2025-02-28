<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BinanceClient
{
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl = 'https://api.binance.com';

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Test connection and get account information
     */
    public function testConnection(): array
    {
        try {
            // Get timestamp for signature
            $timestamp = now()->timestamp * 1000;

            // Create signature
            $signature = hash_hmac('sha256', "timestamp={$timestamp}", $this->apiSecret);

            // Make API call to account endpoint
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->get("{$this->baseUrl}/api/v3/account", [
                'timestamp' => $timestamp,
                'signature' => $signature
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => [
                        'account_type' => $data['accountType'] ?? 'SPOT',
                        'can_trade' => $data['canTrade'] ?? false,
                        'can_withdraw' => $data['canWithdraw'] ?? false,
                        'can_deposit' => $data['canDeposit'] ?? false,
                        'balances' => $this->formatBalances($data['balances'] ?? [])
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid API credentials',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format balances to show only non-zero balances
     */
    protected function formatBalances(array $balances): array
    {
        return collect($balances)
            ->filter(function ($balance) {
                return floatval($balance['free']) > 0 || floatval($balance['locked']) > 0;
            })
            ->map(function ($balance) {
                return [
                    'asset' => $balance['asset'],
                    'free' => floatval($balance['free']),
                    'locked' => floatval($balance['locked']),
                    'total' => floatval($balance['free']) + floatval($balance['locked'])
                ];
            })
            ->values()
            ->toArray();
    }
}

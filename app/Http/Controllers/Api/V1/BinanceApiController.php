<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BinanceApiKey;
use App\Services\BinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Binance
 */
class BinanceApiController extends Controller
{
    protected $binanceService;

    public function __construct(BinanceService $binanceService)
    {
        $this->binanceService = $binanceService;
    }

    /**
     * Store new API credentials
     * @bodyParam api_key string required The Binance API Key. Example: your_api_key
     * @bodyParam api_secret string required The Binance API Secret. Example: your_api_secret
     */
    public function store(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        $result = $this->binanceService->storeApiKeys($request->all(), auth()->id());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Update API credentials
     * @bodyParam id integer required The ID of the API key. Example: 1
     * @bodyParam api_key string required The new Binance API Key. Example: your_new_api_key
     * @bodyParam api_secret string required The new Binance API Secret. Example: your_new_api_secret
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:binance_api_keys,id',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        $apiKey = BinanceApiKey::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $result = $this->binanceService->updateApiKeys($apiKey, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Check API key status
     * @bodyParam id integer required The ID of the API key. Example: 1
     */
    public function status(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:binance_api_keys,id',
        ]);

        $apiKey = BinanceApiKey::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $result = $this->binanceService->checkApiKeyStatus($apiKey);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete API key
     * @bodyParam id integer required The ID of the API key. Example: 1
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:binance_api_keys,id',
        ]);

        $apiKey = BinanceApiKey::where('id', $request->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $result = $this->binanceService->deleteApiKey($apiKey);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Test Binance API connection
     *
     * @param BinanceApiKey $apiKey
     * @return JsonResponse
     */
    public function testConnection(BinanceApiKey $apiKey): JsonResponse
    {
        $result = $this->binanceService->testConnection($apiKey);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}

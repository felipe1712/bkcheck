<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Services\BackgroundCheck\BaseSourceConnector;
use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class NufiConnector extends BaseSourceConnector
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $isMock;
    protected array $lastLog = [];

    public function __construct()
    {
        $this->baseUrl = config('background_check.nufi.base_url', 'https://nufi.azure-api.net');
        $this->apiKey = config('background_check.nufi.api_key', '');
        $this->isMock = config('background_check.nufi.mock', true);
    }

    /**
     * Get the request and response log for this execution.
     */
    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    /**
     * Execute the connector logic.
     */
    public function execute(Subject $subject): array
    {
        if ($this->isMock) {
            Log::info("Ejecutando conector mock: " . $this->getIdentifier() . " para el sujeto: " . $subject->id);
            $mockData = $this->mockResponse($subject);
            
            $this->lastLog = [
                'url' => $this->baseUrl . '/mock/' . $this->getIdentifier(),
                'method' => 'POST',
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => 'MOCK_KEY_MASKED',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    'rfc' => $subject->rfc,
                ],
                'response' => [
                    'status' => 200,
                    'body' => $mockData,
                ]
            ];
            
            return $mockData;
        }

        return $this->callApi($subject);
    }

    /**
     * Perform the actual HTTP call to the NuFi API.
     */
    abstract protected function callApi(Subject $subject): array;

    /**
     * Return simulated high-fidelity mock data.
     */
    abstract protected function mockResponse(Subject $subject): array;

    /**
     * Helper to perform a standard HTTP POST request to NuFi with headers.
     */
    protected function postRequest(string $endpoint, array $body): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception("La clave API de NuFi (Ocp-Apim-Subscription-Key) no está configurada.");
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $this->lastLog = [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Ocp-Apim-Subscription-Key' => '***' . substr($this->apiKey, -4),
                'NUFI-API-KEY' => '***' . substr($this->apiKey, -4),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ];

        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'NUFI-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($url, $body);

            $this->lastLog['response'] = [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];

            if ($response->failed()) {
                Log::error("Error en NuFi API [{$endpoint}]: " . $response->body());
                throw new \Exception("NuFi API retornó código " . $response->status() . ": " . $response->body());
            }

            return $response->json();
        } catch (\Throwable $e) {
            if (!isset($this->lastLog['response'])) {
                $this->lastLog['response'] = [
                    'status' => 0,
                    'body' => $e->getMessage(),
                ];
            }
            throw $e;
        }
    }
}

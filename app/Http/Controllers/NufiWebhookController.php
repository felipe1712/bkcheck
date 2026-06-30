<?php

namespace App\Http\Controllers;

use App\Models\SourceQuery;
use App\Models\SourceResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NufiWebhookController extends Controller
{
    /**
     * Handle incoming webhook callback from NuFi.
     */
    public function handle(Request $request)
    {
        Log::info('NuFi Webhook received:', $request->all());

        $payload = $request->all();
        
        // 1. Try to find the query by UUID or transaction ID
        $uuid = $payload['uuid'] ?? $payload['request_id'] ?? $payload['query_id'] ?? null;
        $rfc = $payload['rfc'] ?? $payload['data']['rfc'] ?? null;
        
        $query = null;
        
        if ($uuid) {
            // Find SourceResult where processed_data has this uuid
            $result = SourceResult::where('processed_data->uuid', $uuid)->first();
            if ($result) {
                $query = $result->sourceQuery;
            }
        }
        
        if (!$query && $rfc) {
            // Find the latest processing query of type 'csd' for a subject with this RFC
            // Since RFC is encrypted in database, we filter the processing CSD queries in memory
            $query = SourceQuery::withoutGlobalScopes()
                ->where('source_type', 'csd')
                ->where('status', 'processing')
                ->get()
                ->first(function ($q) use ($rfc) {
                    return $q->subject && strcasecmp($q->subject->rfc, $rfc) === 0;
                });
        }

        if (!$query) {
            Log::warning('NuFi Webhook: No matching processing SourceQuery found for webhook.', $payload);
            return response()->json(['status' => 'ignored', 'message' => 'No matching active query found.'], 200);
        }

        // 2. Extract certificates or error status
        $status = $payload['status'] ?? 'success';
        
        if ($status === 'failed' || isset($payload['error']) || isset($payload['err'])) {
            $errorMsg = $payload['error'] ?? $payload['message'] ?? 'Fallo en la obtención asíncrona de certificados.';
            $query->update([
                'status' => 'failed',
                'error_message' => $errorMsg,
            ]);
            Log::error("NuFi Webhook: Query ID {$query->id} marked as failed from webhook. Error: {$errorMsg}");
        } else {
            // Update the SourceResult with the actual certificates data
            $result = $query->result;
            
            // NuFi might send certificates list under 'certificados', 'data.certificados', or 'data'
            $certificados = $payload['certificados'] ?? $payload['data']['certificados'] ?? $payload['data'] ?? [];
            
            // Normalize structure if it comes as key-value list of certificates
            if (isset($certificados['certificados']) && is_array($certificados['certificados'])) {
                $certificados = $certificados['certificados'];
            }

            if ($result) {
                $processedData = $result->processed_data;
                $processedData['certificados'] = $certificados;
                $result->update([
                    'processed_data' => $processedData,
                ]);
            }
            
            $query->update([
                'status' => 'completed',
                'error_message' => null,
            ]);
            
            Log::info("NuFi Webhook: Query ID {$query->id} updated successfully with certificates.");
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully.'], 200);
    }
}

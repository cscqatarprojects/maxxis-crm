<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * Handle chatbot webhook for lead creation
     */
    public function handleChatbotLead(Request $request): JsonResponse
    {
        try {
            Log::info('Chatbot webhook received', [
                'headers' => $request->headers->all(),
                'body'    => $request->all(),
            ]);

            // Validate the webhook payload
            $validator = Validator::make($request->all(), [
                'title'               => 'required|string|max:255',
                'contact_name'        => 'required|string|max:255',
                'car_make'            => 'nullable|string|max:100',
                'car_model'           => 'nullable|string|max:100',
                'car_year'            => 'nullable|string|max:4',
                'car_type'            => 'nullable|string|max:50',
                'tire_size'           => 'nullable|string|max:50',
                'lead_type'           => 'nullable|string|max:100',
                'lead_source'         => 'nullable|string|max:100',
                'description'         => 'nullable|string',
                'expected_close_date' => 'nullable|date|after:today',
                'lead_value'          => 'nullable|numeric|min:0',
                'sales_owner'         => 'nullable|email|exists:users,email',
            ]);

            if ($validator->fails()) {
                Log::warning('Webhook validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Forward to lead controller
            $leadController = new LeadController(
                app(\Webkul\Lead\Repositories\LeadRepository::class),
                app(\Webkul\Contact\Repositories\PersonRepository::class),
                app(\Webkul\User\Repositories\UserRepository::class),
                app(\Webkul\Lead\Repositories\SourceRepository::class),
                app(\Webkul\Lead\Repositories\TypeRepository::class),
                app(\Webkul\Lead\Repositories\PipelineRepository::class),
                app(\Webkul\Lead\Repositories\StageRepository::class)
            );

            return $leadController->store($request);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check for webhook endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toISOString(),
            'service'   => 'Maxxiss CRM Webhook Service',
        ]);
    }
}

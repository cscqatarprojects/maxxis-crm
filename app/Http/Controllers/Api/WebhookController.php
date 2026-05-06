<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\User\Repositories\UserRepository;

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
                app(LeadRepository::class),
                app(PersonRepository::class),
                app(UserRepository::class),
                app(SourceRepository::class),
                app(TypeRepository::class),
                app(PipelineRepository::class),
                app(StageRepository::class)
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

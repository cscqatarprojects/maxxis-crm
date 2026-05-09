<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Handle chatbot webhook for adding a conversation note to a lead
     */
    public function handleChatbotLeadNote(Request $request, $lead_id): JsonResponse
    {
        try {
            Log::info('Chatbot note webhook received', [
                'lead_id' => $lead_id,
                'headers' => $request->headers->all(),
                'body'    => $request->all(),
            ]);

            $validator = Validator::make($request->all(), [
                'note' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Chatbot note webhook validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $leadRepository     = app(\Webkul\Lead\Repositories\LeadRepository::class);
            $activityRepository = app(\Webkul\Activity\Repositories\ActivityRepository::class);

            $lead = $leadRepository->find($lead_id);

            if (! $lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found',
                ], 404);
            }

            DB::beginTransaction();

            try {
                $activity = $activityRepository->create([
                    'type'    => 'note',
                    'title'   => 'Chatbot Conversation',
                    'comment' => $request->input('note'),
                    'is_done' => 1,
                    'user_id' => $lead->user_id,
                ]);

                $lead->activities()->attach($activity->id);

                DB::commit();

                Log::info('Chatbot note created successfully', [
                    'lead_id'     => $lead->id,
                    'activity_id' => $activity->id,
                ]);

                return response()->json([
                    'success'     => true,
                    'activity_id' => $activity->id,
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to create chatbot note', [
                    'error'   => $e->getMessage(),
                    'lead_id' => $lead_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create note',
                    'error'   => $e->getMessage(),
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Chatbot note webhook processing error', [
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\User\Repositories\UserRepository;

class LeadController extends Controller
{
    public function __construct(
        protected LeadRepository $leadRepository,
        protected PersonRepository $personRepository,
        protected UserRepository $userRepository,
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
        protected PipelineRepository $pipelineRepository,
        protected StageRepository $stageRepository
    ) {
        // Set entity type for the request
        request()->request->add(['entity_type' => 'leads']);
    }

    /**
     * Create a new lead from webhook data
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the incoming request
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
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $data = $request->all();

            DB::beginTransaction();

            try {
                // Find or create person
                $person = $this->findOrCreatePerson($data);

                // Find or create lead source
                $leadSource = $this->findOrCreateLeadSource($data['lead_source'] ?? 'Chatbot');

                // Find or create lead type
                $leadType = $this->findOrCreateLeadType($data['lead_type'] ?? 'New Business');

                // Get default pipeline and first stage
                $pipeline = $this->pipelineRepository->getDefaultPipeline();
                $stage = $pipeline->stages()->first();

                // Find sales owner user
                $user = null;
                if (! empty($data['sales_owner'])) {
                    $user = $this->userRepository->findWhere(['email' => $data['sales_owner']])->first();
                }

                // Prepare lead data
                $leadData = [
                    'title'                  => $data['title'],
                    'description'            => $this->buildDescription($data),
                    'lead_value'             => $data['lead_value'] ?? 0,
                    'status'                 => 1, // Active
                    'expected_close_date'    => $data['expected_close_date'] ?? now()->addDays(30)->format('Y-m-d'),
                    'user_id'                => $user ? $user->id : 1, // Default to admin user if not found
                    'person_id'              => $person->id,
                    'lead_source_id'         => $leadSource->id,
                    'lead_type_id'           => $leadType->id,
                    'lead_pipeline_id'       => $pipeline->id,
                    'lead_pipeline_stage_id' => $stage->id,
                ];

                // Create the lead
                $lead = $this->leadRepository->create($leadData);

                // Add custom attributes for car details
                $this->addCustomAttributes($lead, $data);

                DB::commit();

                Log::info('Lead created successfully via API', [
                    'lead_id'   => $lead->id,
                    'title'     => $lead->title,
                    'person_id' => $person->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Lead created successfully in CRM',
                    'data'    => [
                        'lead_id'     => $lead->id,
                        'title'       => $lead->title,
                        'person_name' => $person->name,
                        'status'      => 'created',
                        'created_at'  => $lead->created_at->format('Y-m-d H:i:s'),
                    ],
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to create lead via API', [
                    'error' => $e->getMessage(),
                    'data'  => $data,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create lead in CRM',
                    'error'   => $e->getMessage(),
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('API Lead creation error', [
                'error'        => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find or create person
     */
    private function findOrCreatePerson(array $data)
    {
        $personData = [
            'entity_type' => 'persons',
            'name'        => $data['contact_name'],
            'emails'      => [
                [
                    'value' => 'chatbot-'.time().'@example.com',
                    'label' => 'work',
                ],
            ],
        ];

        // Try to find existing person by name
        $person = $this->personRepository->findWhere(['name' => $data['contact_name']])->first();

        if (! $person) {
            $person = $this->personRepository->create($personData);
        }

        return $person;
    }

    /**
     * Find or create lead source
     */
    private function findOrCreateLeadSource(string $sourceName)
    {
        $source = $this->sourceRepository->findWhere(['name' => $sourceName])->first();

        if (! $source) {
            $source = $this->sourceRepository->create([
                'name'       => $sourceName,
                'is_default' => 0,
            ]);
        }

        return $source;
    }

    /**
     * Find or create lead type
     */
    private function findOrCreateLeadType(string $typeName)
    {
        $type = $this->typeRepository->findWhere(['name' => $typeName])->first();

        if (! $type) {
            $type = $this->typeRepository->create([
                'name'       => $typeName,
                'is_default' => 0,
            ]);
        }

        return $type;
    }

    /**
     * Build description from car details
     */
    private function buildDescription(array $data): string
    {
        $description = $data['description'] ?? '';

        $carDetails = [];
        if (! empty($data['car_make'])) {
            $carDetails[] = "Make: {$data['car_make']}";
        }
        if (! empty($data['car_model'])) {
            $carDetails[] = "Model: {$data['car_model']}";
        }
        if (! empty($data['car_year'])) {
            $carDetails[] = "Year: {$data['car_year']}";
        }
        if (! empty($data['car_type'])) {
            $carDetails[] = "Type: {$data['car_type']}";
        }
        if (! empty($data['tire_size'])) {
            $carDetails[] = "Tire Size: {$data['tire_size']}";
        }

        if (! empty($carDetails)) {
            $carInfo = 'Car Details: '.implode(', ', $carDetails);
            $description = $description ? $description."\n\n".$carInfo : $carInfo;
        }

        return $description;
    }

    /**
     * Add custom attributes for car details
     */
    private function addCustomAttributes($lead, array $data)
    {
        // This would require custom attribute setup in the CRM
        // For now, we'll include the car details in the description
        // In a full implementation, you'd create custom attributes for:
        // - car_make, car_model, car_year, car_type, tire_size
    }
}

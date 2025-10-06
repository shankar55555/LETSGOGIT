<?php

namespace Modules\Clients\Http\Controllers;

use App\Constants\CommonConst;
use App\Http\Controllers\Controller;
use App\Models\AdminControlConfig;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;
use Modules\AlertAndNotification\Jobs\NotificationJob;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Services\ClientService;
use Modules\Clients\Http\Requests\{ClientStoreRequest, ClientUpdateRequest};
use Modules\Clients\Models\Client;
use Modules\Clients\Models\ClientAttachment;
use Modules\Clients\Transformers\ClientResource;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {

        $this->clientService = $clientService;
    }

    public function optionClientList(Request $request)
    {
        try {
            $clients = $this->clientService->getActiveClientOptions();
            return $this->actionSuccess("Client Option List get Successfully", $clients);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function index(Request $request)
    {
        try {
            $statusList = $request->has('status_list')
                ? (is_array($request->status_list) ? $request->status_list : explode(',', $request->status_list))
                : [];

            $clients = $this->clientService->getPaginatedClients(
                $request->boolean('with_trashed'),
                $request->input('status'),
                $request->input('search'),
                $statusList,
                $request->integer('per_page', 15),
                $request->input('user_view_id')
            );

            return response()->json([
                'data' => ClientResource::collection($clients),
                'meta' => $this->clientService->buildPaginationMeta($clients),
                'status' => Response::HTTP_OK,
            ]);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function store(ClientStoreRequest $request)
    {
        try {
            $client = $this->clientService->createClient(
                $request->validated(),
                Auth::user()->uuid
            );

            # Lead created
            NotificationJob::dispatch(ClientConst::RULE_CLIENT_CREATED, clientRuleNotification($client->id), null, loginUserId());

            # Lead assigned to user
            if ($client->assigned_user) {
                NotificationJob::dispatch(ClientConst::RULE_ASSIGNED_TO_USER, clientRuleNotification($client->id), null, loginUserId());
            }

            return response()->json([
                'message' => __('Client created successfully'),
                'data' => new ClientResource($client),
                'status' => Response::HTTP_OK
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function show(Client $client)
    {
        try {
            $client = $this->clientService->loadClientRelations($client);
            return response()->json([
                'data' => new ClientResource($client)
            ]);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function update(ClientUpdateRequest $request, Client $client)
    {
        try {
            $assigned_user = Client::where('id', $client->id)->pluck('assigned_user')->first();
            $client = $this->clientService->updateClient(
                $client,
                $request->validated(),
                Auth::user()->uuid
            );

            # Client assigned to user
            if ($client->assigned_user != $assigned_user) {
                NotificationJob::dispatch(ClientConst::RULE_ASSIGNED_TO_USER, clientRuleNotification($client->id), null, loginUserId());
            }

            return response()->json([
                'message' => __('Client updated successfully'),
                'data' => new ClientResource($client->fresh()),
                'status' => Response::HTTP_OK
            ]);
        } catch (\Exception $e) {
            return $this->actionFailure($e->getMessage());
        }
    }

    public function destroy(Client $client)
    {
        try {
            $result = $this->clientService->softDeleteClient($client);

            return response()->json([
                'success' => true,
                'message' => __('Client marked as deleted successfully'),
                'status' => Response::HTTP_OK,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to delete client'),
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function restore($id): JsonResponse
    {
        try {
            $client = $this->clientService->restoreClient($id);

            return response()->json([
                'success' => true,
                'message' => __('Client restored successfully'),
                'data' => $client
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to restore client'),
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request, Client $client)
    {
        DB::beginTransaction();
        try {
            $oldStatus = $client->status;
            $updatedClient = $this->clientService->updateClientStatus($client, $request->input('status'), Auth::user()->uuid);

            $RuleCheckHelper = new RuleCheckHelper();
            $RuleCheckHelper->onlyStatusChangeCheckRule(CommonConst::MODULE_CLIENT, $request->input('status'), [$client->id], $oldStatus);

            // $method = 'manual';
            // $statusTriggerList = AdminControlConfig::where('status_for', CommonConst::MODULE_CLIENT)->where('slug', $request->input('status'))->pluck('trigger_action')->first() ?? [];
            // if (in_array(ClientConst::CONVERT_TO_LEAD, $statusTriggerList)) {
            //     $this->clientService->convertToLead($client->id, $method,loginUserId());
            // }

            # Client assigned to user
            // if ($updatedClient->status == CommonConst::IN_ACTIVE) {
            //     NotificationJob::dispatch(ClientConst::RULE_CLIENT_INACTIVE, clientRuleNotification($client->id),null, loginUserId());
            // }

            DB::commit();
            return $this->actionSuccess("Client status updated successfully", new ClientResource($updatedClient));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->actionFailure($e->getMessage());
        }
    }

    public function clientAttachments($id)
    {
        $client = ClientAttachment::where('client_id', $id)->get();

        return response()->json([
            'message' => 'Client attachments fetched successfully',
            'data' => $client
        ]);
    }
}

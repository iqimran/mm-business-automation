<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Services\ClientService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Spatie\QueryBuilder\QueryBuilder;
use App\Http\Resources\ClientResource;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use Exception;

class ClientController extends Controller
{
    protected ClientService $clientService;
    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
          
    public function index(Request $request)
    {
        $cacheKey = 'clients:' . md5(serialize($request->all())) . ':page:' . $request->get('page', 1);
        if (cache()->has($cacheKey)) {
            return response()->json(cache()->get($cacheKey));
        }

        $clients = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            return QueryBuilder::for(Client::class)
                ->with(['sales'])
                ->withCount(['sales'])
                ->allowedFilters([
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'city',
                    'state',
                    AllowedFilter::exact('is_active'),
                ])
                ->allowedSorts(['first_name', 'last_name', 'email', 'created_at'])
                ->defaultSort('first_name')
                ->paginate($request->per_page ?? 15);
            //ClientResource::collection($this->clientService->getAllClients($request->all())->paginate(10));
        });

        return new ClientResource($clients);
    }

    public function show(Client $client)
    {
        $cacheKey = "client:{$client->id}:details";

        $clientData = Cache::remember($cacheKey, now()->addHours(6), function () use ($client) {
            return $client->load(['sales.car', 'sales.payments']);
        });

        return response()->json([
            'success' => true,
            'data' => new ClientResource($clientData)
        ]);
    }

    public function store(StoreClientRequest $request)
    {
        try {
            $client = $this->clientService->createClient($request->validated());

            // Cache the new client
            Cache::put("client:{$client->id}", $client, now()->addHours(24));

            // Clear clients list cache
            Cache::tags(['clients'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => new ClientResource($client)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        try {
            $updatedClient = $this->clientService->updateClient($client, $request->validated());

            // Update cache
            Cache::put("client:{$client->id}", $updatedClient, now()->addHours(24));
            Cache::forget("client:{$client->id}:details");
            Cache::tags(['clients'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Client updated successfully',
                'data' => new ClientResource($updatedClient)
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            $deleted = $this->clientService->deleteClient($client);
            // Clear cache
            Cache::forget("client:{$client->id}");
            Cache::forget("client:{$client->id}:details");
            Cache::tags(['clients'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully'
            ],200);
        }
        catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete client',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}

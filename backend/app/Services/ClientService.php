<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientService
{
    public function getAllClients(): Collection
    {
        return Client::all();
    }

    public function createClient(array $data): Client
    {
        return Client::create($data);
    }

     public function find(Client $client): Client
    {
        return $client;
    }

    public function updateClient(Client $client, array $data): Client
    {
        $client->update($data);
        return $client;
    }

    public function deleteClient(Client $client): void
    {
        $client->delete();
    }
}

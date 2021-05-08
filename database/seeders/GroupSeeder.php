<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;
use GuzzleHttp\Client;

class GroupSeeder extends Seeder
{

    public function run()
    {
        $this->client = new Client(
            ['base_uri' => 'https://portal.kuzstu.ru/api/groups']
        );

        $response = $this->client->request('GET');
        $groups = json_decode($response->getBody()->getContents(), true);
        $uniqueGroups = collect($groups)->unique('id')->toArray();

        foreach ($uniqueGroups as $group){
            Group::create([
                'id_portal' => $group['id'],
                'name' => $group['name']
            ]);
        }


    }
}

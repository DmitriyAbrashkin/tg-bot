<?php


namespace App\Services;


use GuzzleHttp\Client;

class ScheduleService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(
            ['base_uri' => 'https://portal.kuzstu.ru/api']
        );
    }

    public function getScheduleForGroup($id)
    {
        $response = $this->client->request('GET', 'student_schedule', [
            'query' => [
                'group_id' => $id
            ]
        ]);
        $groups = json_decode($response->getBody()->getContents(), true);


    }
}

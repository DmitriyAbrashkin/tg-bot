<?php


namespace App\Services;


use GuzzleHttp\Client;

class MessageService
{
    private $baseUrl;
    private $token;
    private $client;
    private $palindromeService;

    public function __construct()
    {
        $this->baseUrl = env('TELEGRAM_API_URL');
        $this->token = env("TELEGRAM_BOT_TOKEN");
        $this->palindromeService = new PhraseService();
        $this->client = new Client(
            ['base_uri' => $this->baseUrl . 'bot' . $this->token . '/']
        );

    }

    public function getUpdates()
    {

        $response = $this->client->request('GET', 'getUpdates');

        if ($response->getStatusCode() === 200) {
            $messages = json_decode($response->getBody()->getContents(), true);
            foreach ($messages['result'] as $result) {
                if (isset($result['message']['text'])) {

                    $update_id = $result['update_id'];
                    $phrase = $result['message']['text'];
                    $chatId = $result['message']['from']['id'];

                    $palindrome = $this->palindromeService->isPalindrome($phrase);

                    $this->sendMessages($chatId, $palindrome);
                    $this->clearUpdates($update_id);

                }
            }

        }

    }


    public function sendMessages($chatId, $text)
    {
        $response = $this->client->request('GET', 'sendMessage', [
            'query' => [
                'chat_id' => $chatId,
                'text' => $text
            ]
        ]);
    }

    public function clearUpdates($offset): void
    {
        $this->client->request('GET', 'getUpdates', [
            'query' => [
                'offset' => $offset + 1
            ]
        ]);
    }


}

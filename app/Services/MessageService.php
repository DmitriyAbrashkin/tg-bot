<?php


namespace App\Services;

use GuzzleHttp\Client;
use Predis\Client as Predis;

class MessageService
{
    private $baseUrl;
    private $token;
    private $client;
    private $arrToKtService;
    private $predisClient;

    public function __construct()
    {
        $this->baseUrl = env('TELEGRAM_API_URL');
        $this->token = env("TELEGRAM_BOT_TOKEN");
        $this->arrToKtService = new ArrToStrKtService();
        $this->client = new Client(
            ['base_uri' => $this->baseUrl . 'bot' . $this->token . '/']
        );
        $this->predisClient = new Predis();


    }

    public function getUpdates()
    {
        $offset = $this->predisClient->get('update_id');

        $response = $this->client->request('GET', 'getUpdates', [
            'query' => [
                'offset' => $offset + 1
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $messages = json_decode($response->getBody()->getContents(), true);
            foreach ($messages['result'] as $result) {
                if (isset($result['message']['text'])) {

                    $this->predisClient->set('update_id', $result['update_id']);
                    $phrase = $result['message']['text'];
                    $chatId = $result['message']['from']['id'];

                    /* Типа проверка что попросили написать номер зачетки и дальше нам нужно попасть в функцию test чтобы там
                                         выполнить валидацию, запрос, сформировать ответ и ответить, уставноить этап диалога на 0/удалить запиьс из редиса*/
                    $fucName = $this->predisClient->get($chatId);
                    if ($fucName != null && method_exists($this, $fucName)) {
                        $this->{$fucName}($phrase, $chatId);
                    }


                    /*Сюда разные команды в swith засунуть
                    просьба ввести необходимый параметр и установка следующего обработчика*/
                    if ($phrase == 'КТ') {
                        $this->setNextHandler($chatId, 'test');
                        $this->sendMessages($chatId, 'Напишите номер зачетки');
                    }
                }
            }
        }
    }

    public function test($phrase, $chatId)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = new ParserKtService();
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtService->toStr($studentInfo));
            } catch (\Exception $exception) {
                $this->sendMessages($chatId, 'Что-то пошло не так. Попробуйте позже');
            }
            $this->setNextHandler($chatId, 'test2');
        } else {
            $this->sendMessages($chatId, 'Неккоректный номер зачетки' . PHP_EOL . 'Пожалуйста, повторите попытку.');
        }
    }

    public function test2($phrase, $chatId){
        $this->setNextHandler($chatId, null);
        $this->sendMessages($chatId, 'Могу ли я еще чем-нибудь помочь?');
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


    public function setNextHandler($chatId, $funcName): void
    {
        $this->predisClient->set($chatId, $funcName);
    }
}

/*
                  * TODO:
                  *  + написать сервис для заполнения предмета и статы о нем
                  *  - формиравать ответ из базы если есть запись
                  *  - объединить сервисы для парсера в папку
                  *  - отрефакторить парсер
                  *  -
                  *  + написать сидер для групп
                  *  - написать сервис для работы с апи групп
                  *  - сохранять результат в редис
                  *  - написать сервис для запоминаниля пользователя: имя, ид, номер зачетки, группа
                  *  - добавить клавиатуру: расписание, кт...
                  *  - сохранять состояние диалога в редисе... (сохранять ид пользователя и название функции, которая должна выполниться следующей.)
                  *
                  *
                  *
                  *
                  * */

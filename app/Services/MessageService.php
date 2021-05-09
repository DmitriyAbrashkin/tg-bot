<?php


namespace App\Services;

use App\Services\ParserKT\ArrToStrKtService;
use App\Services\ParserKT\ParserKtService;
use GuzzleHttp\Client;
use Predis\Client as Predis;

class MessageService
{
    private $baseUrl;
    private $token;
    private $client;
    private $arrToKtService;
    private $predisClient;
    private $userService;
    private $subjectService;
    private $tasksService;

    public function __construct()
    {
        $this->baseUrl = env('TELEGRAM_API_URL');
        $this->token = env("TELEGRAM_BOT_TOKEN");
        $this->arrToKtService = new ArrToStrKtService();
        $this->client = new Client(
            ['base_uri' => $this->baseUrl . 'bot' . $this->token . '/']
        );
        $this->predisClient = new Predis();
        $this->userService = new UserService();
        $this->subjectService = new SubjectService();
        $this->tasksService = new TaskService();

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


                    $fucName = $this->predisClient->get($chatId);
                    if ($fucName != null && method_exists($this, $fucName)) {
                        $this->{$fucName}($phrase, $chatId); // магия php
                    } else {
                        switch ($phrase) {
                            case '/start':
                                $this->userService->saveInfoAboutUser(
                                    $result['message']['from']['first_name'],
                                    $result['message']['from']['last_name'],
                                    $result['message']['from']['username'],
                                    $chatId
                                );
                                $this->sendMessages($chatId, 'Привет {рассказать про функции}');
                                break;
                            case 'КТ':
                                $user = $this->userService->getInfoAboutUser($chatId);
                                if ($user != null && $user->student_number != null) {
                                    $this->getKT($user->student_number, $chatId, true);
                                } else {
                                    $this->setNextHandler($chatId, 'getKT');
                                    $this->sendMessages($chatId, 'Напишите номер зачетки');
                                }
                                break;
                            case 'Добавить категорию':
                                $this->setNextHandler($chatId, 'addSubject');
                                $this->sendMessages($chatId, 'Введите название категории:');
                                break;
                            case 'Посмотреть все категории':
                                $this->showAllSubject($chatId);
                                break;

                            default:
                                $this->sendMessages($chatId, 'Неизвестная команда');
                                break;
                        }
                    }
                } elseif (isset($result['callback_query'])) {
                    $this->predisClient->set('update_id', $result['update_id']);

                    $chatId = $result['callback_query']['from']['id'];
                    $phrase = $result['callback_query']['data'];


                    $this->actionInlineButtonSubjects($chatId, $phrase);
                }
            }
        }
    }


    public function addSubject($phrase, $chatId)
    {
        $this->subjectService->addSubject($phrase, $chatId);
        $this->sendMessages($chatId, 'Категория успешно добавлена');
        $this->showAllSubject($chatId);
        $this->setNextHandler($chatId, null);
    }

    public function actionInlineButtonSubjects($chatId, $callback_data)
    {
        $params = explode("_", $callback_data);
        if($params[0] = 'showTaskForSubjectId'){
            $asnser = $this->tasksService->getTasksForSubject($params[1]);
            $this->sendMessages($chatId, 'Описание задачи', $asnser);
        }

    }


    public function sendMessages($chatId, $text, $keyboard = '')
    {
        $response = $this->client->request('GET', 'sendMessage', [
            'query' => [
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $keyboard
            ]
        ]);
    }

    public function setNextHandler($chatId, $funcName): void
    {
        $this->predisClient->set($chatId, $funcName);
    }

    public function getKT($phrase, $chatId, $isSave = false)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = new ParserKtService();
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtService->toStr($studentInfo));

                if (!$isSave) {
                    $this->predisClient->set('sn' . $chatId, $phrase);
                    $this->setNextHandler($chatId, 'saveStudentNumber');
                    $this->sendMessages($chatId, 'Сохранить этот номер зачетки для последующих запросов?');
                }
            } catch (\Exception $exception) {
                $this->sendMessages($chatId, 'Что-то пошло не так. Попробуйте позже');
                $this->setNextHandler($chatId, null);
            }
        } else {
            $this->sendMessages($chatId, 'Неккоректный номер зачетки' . PHP_EOL . 'Пожалуйста, повторите попытку.');
        }
    }

    public function saveStudentNumber($phrase, $chatId)
    {
        if ($phrase == 'Да') {
            $studentNumber = $this->predisClient->get('sn' . $chatId);
            $this->userService->saveStudentNumber($chatId, $studentNumber);
        }
        $this->setNextHandler($chatId, null);
    }

    public function showAllSubject($chatId): void
    {
        $subjects = $this->subjectService->getAllForUser($chatId);
        $answer = $this->subjectService->getAnswerAllSubject($subjects);
        $this->sendMessages($chatId, 'Категории:', $answer);
    }

}

/*
 * TODO:
 *  - Выдавать все задачи по нажатию кнопки(с кнопками редактировать, удалить и начать задачу(время начала и время конца))
 *  - потом для отчетности - время потраченное на задачу: сумма разниц времен всех записей для этой задачи
 *  - Добавлять задачу
 *  - Таблица с записями о задачах кт начинали/закончили
 *  -   - ид
 *  -   - ид задачи
 *  -   - дата-время начала задачи
 *  -   - дата-время конца задачи
 *  - Выдавать категории (название и три кнопки (отредактировать, удалить, показать задачи))
 *  -
 *  -
 * */

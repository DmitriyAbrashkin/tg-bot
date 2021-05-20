<?php


namespace App\Services;

use App\Jobs\ProcessPomodoroTimer;
use App\Models\Subject;
use App\Services\ParserKT\ArrToStrKtService;
use App\Services\ParserKT\ParserKtService;
use GuzzleHttp\Client;
use Predis\Client as Predis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
    private $keyBoardService;

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
        $this->keyBoardService = new KeyboardService();

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
                    Log::channel('daily')->info(': Сообщение от : ' . $result['message']['from']['username'] . ' - ' . $phrase);

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

                                $this->sendMessages(
                                    $chatId,
                                    'Привет {рассказать про функции}',
                                    $this->keyBoardService->getMainKeyboard()
                                );

                                break;
                            case 'Мои КТ':
                                $user = $this->userService->getInfoAboutUser($chatId);
                                if ($user != null && $user->student_number != null) {
                                    $this->getMyKT($user->student_number, $chatId, false);
                                } else {
                                    $this->setNextHandler($chatId, 'getMyKT');
                                    $this->sendMessages($chatId, 'Напишите номер зачетки');
                                }
                                break;
                            case 'КТ':
                                $this->setNextHandler($chatId, 'getKT');
                                $this->sendMessages($chatId, 'Напишите номер зачетки');
                                break;
                            case 'Предметы':
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
                    $this->answerCallbackQuery($result['callback_query']);

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
        if ($params[0] = 'startPomodoroForId') {
            $subject = Subject::findOrFail($params[1]);
            $job = (new ProcessPomodoroTimer($subject));
            dispatch($job)->delay(now()->addMinutes(1));

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

    public function answerCallbackQuery($callback_data)
    {
        $response = $this->client->request('GET', 'answerCallbackQuery', [
            'query' => [
                "callback_query_id" => $callback_data["id"],
                "text" => "Помидор установлен",
                "alert" => true
            ]
        ]);
    }


    public function setNextHandler($chatId, $funcName): void
    {
        $this->predisClient->set($chatId, $funcName);
    }

    public function getKT($phrase, $chatId)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = new ParserKtService();
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtService->toStr($studentInfo));
            } catch (\Exception $ex) {
                $this->sendMessages($chatId, 'Что-то пошло не так. Попробуйте позже');
            } finally {
                $this->setNextHandler($chatId, null);
            }
        } else {
            $this->sendMessages($chatId, 'Неккоректный номер зачетки' . PHP_EOL . 'Пожалуйста, повторите попытку.');
        }
    }

    public function getMyKT($phrase, $chatId, $isNeedToSave = true)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = new ParserKtService();
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtService->toStr($studentInfo));

                if ($isNeedToSave) {
                    $this->predisClient->set('sn' . $chatId, $phrase);
                    $this->predisClient->set('si' . $chatId, serialize($studentInfo));
                    $this->setNextHandler($chatId, 'saveStudentNumber');
                    $this->sendMessages(
                        $chatId,
                        'Сохранить этот номер зачетки для последующих запросов?',
                        $this->keyBoardService->getKeyboardYesOrNo()
                    );


                } else {
                    $this->setNextHandler($chatId, null);
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
            $studentInfo = unserialize($this->predisClient->get('si' . $chatId));
            $this->subjectService->saveSubjects($studentInfo, $chatId);
            $this->userService->saveStudentNumber($chatId, $studentNumber);

            $this->sendMessages(
                $chatId,
                'Успешно сохранено',
                $this->keyBoardService->getMainKeyboard()
            );
        } else {
            $this->sendMessages($chatId, 'Хорошо ;)', $this->keyBoardService->getMainKeyboard());
        }
        $this->setNextHandler($chatId, null);
    }

    public function showAllSubject($chatId): void
    {
        $subjects = $this->subjectService->getAllForUser($chatId);
        $answer = $this->subjectService->getAnswerAllSubject($subjects);
        $this->sendMessages($chatId, 'Предметы:', $answer);
    }

}

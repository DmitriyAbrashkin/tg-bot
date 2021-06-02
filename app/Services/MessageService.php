<?php


namespace App\Services;

use App\Jobs\ProcessPomodoroTimer;
use App\Models\Subject;
use App\Models\User;
use App\Services\Keyboard\Abstracts\KeyboardInterface;
use App\Services\ParserKT\Abstracts\ArrToStrKtInterface;
use App\Services\ParserKT\Abstracts\ParserKtInterface;
use App\Services\Pomodoro\Abstracts\PomodoroInterface;
use App\Services\Subject\Abstracts\SubjectInterface;
use App\Services\Task\Abstracts\TaskInterface;
use App\Services\User\Abstracts\UserInterface;
use GuzzleHttp\Client;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\DB;
use Predis\Client as Predis;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isEmpty;

/**
 * Class MessageService
 * @package App\Services
 */
class MessageService
{
    private $baseUrl;
    private $token;
    private $client;
    private $predisClient;
    private ArrToStrKtInterface $arrToKtInterface;
    private UserInterface $userInterface;
    private SubjectInterface $subjectInterface;
    private KeyboardInterface $keyBoardInterface;
    private ParserKtInterface $parserKtInterface;
    private TaskInterface $tasksInterface;
    private string $elementId;

    public function __construct(
        ArrToStrKtInterface $arrToKtInterface,
        UserInterface $userInterface,
        SubjectInterface $subjectInterface,
        TaskInterface $tasksInterface,
        KeyboardInterface $keyBoardInterface,
        ParserKtInterface $parserKtInterface
    )
    {
        $this->baseUrl = env('TELEGRAM_API_URL');
        $this->token = env("TELEGRAM_BOT_TOKEN");

        $this->client = new Client(
            ['base_uri' => $this->baseUrl . 'bot' . $this->token . '/']
        );

        $this->predisClient = new Predis([
            'scheme' => 'tcp',
            'host' => config("database.redis.default.host"),
            'port' => 6379,
        ]);

        $this->userInterface = $userInterface;
        $this->subjectInterface = $subjectInterface;
        $this->tasksInterface = $tasksInterface;
        $this->keyBoardInterface = $keyBoardInterface;
        $this->arrToKtInterface = $arrToKtInterface;
        $this->parserKtInterface = $parserKtInterface;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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

                    $params = explode("_", $fucName);
                    if (isset($params[1])) $this->elementId = $params[1];
                    else $this->elementId = "";
                    // $this->sendMessages($chatId, $this->elementId);
                    if ($phrase != 'Назад' && $params[0] != null && method_exists($this, $params[0])) {
                        $this->{$params[0]}($phrase, $chatId); // магия php
                    } else {
                        switch ($phrase) {
                            case '/start':

                                $this->userInterface->saveInfoAboutUser(
                                    $result['message']['from']['first_name'] ?? "noName",
                                    $result['message']['from']['last_name'] ?? "noLastName",
                                    $result['message']['from']['username'] ?? "noUsername",
                                    $chatId
                                );

                                $this->sendMessages(
                                    $chatId,
                                    'Привет {рассказать про функции}',
                                    $this->keyBoardInterface->getMainKeyboard()
                                );

                                break;
                            case 'Мои КТ':
                                $user = $this->userInterface->getInfoAboutUser($chatId);
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
                            case 'Помощь':
                                $this->sendMessages($chatId, 'https://telegra.ph/Kak-rabotat-s-botom-05-20');
                                break;
                            case 'Профиль':
                                $this->statistics($chatId);
                                break;
                            case 'Изменить номер зачетки':
                                $this->setNextHandler($chatId, 'getMyKT');
                                $this->sendMessages($chatId, 'Напишите номер зачетки');
                                break;
                            case 'Изменить время помидора':
                                $this->setNextHandler($chatId, 'setNewPomodoroTimer');
                                $this->sendMessages($chatId, 'Напишите время нового таймера в минутах');
                                break;
                            case 'Назад':
                                $this->setNextHandler($chatId, null);
                                $this->sendMessages($chatId, 'Чем я могу вам помочь?', $this->keyBoardInterface->getMainKeyboard());
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

                    $this->actionInlineButtonSubjects($chatId, $phrase, $result['callback_query']);

                }
            }
        }
    }

    public function setNewPomodoroTimer($phrase, $chatId)
    {
        if (ctype_digit($phrase) && $phrase > 0) {
            $this->userInterface->setNewPomodoroTimer($chatId, $phrase);
            $this->setNextHandler($chatId, null);
            $this->sendMessages($chatId, 'Успешно сохранено');
        } else {
            $this->sendMessages($chatId, 'Некорректное время, пожалуйста введите число больше 0');
        }

    }

    /**
     * @param $chatId
     */
    public function statistics($chatId)
    {
        $user = $this->userInterface->getInfoAboutUser($chatId);
        $subjects = $this->subjectInterface->getAllForUser($chatId);
        $allPomodoro = 0;
        $result = '';
        foreach ($subjects as $subject) {
            $result .= $subject->name . ' -  ' . $subject->count_pomodoro . PHP_EOL;
            $allPomodoro += $subject->count_pomodoro;
        }

        $this->sendMessages(
            $chatId,

            $user->first_name . PHP_EOL .
            'Уровень ' . $user->level . PHP_EOL .
            'Время помидора ' . $user->pomodoro_time . PHP_EOL .
            $result . PHP_EOL .
            'Всего помидоров ' . $allPomodoro,
            $this->keyBoardInterface->getProfileKeyboard()

        );
    }

    /**
     * @param $chatId
     * @param $callback_data
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function actionInlineButtonSubjects($chatId, $callback_data, $result)
    {
        $params = explode("_", $callback_data);

        //Если не работаю кнопки закомменти условие
//        if ($params[0] = 'startPomodoroForId') {
//            $subject = Subject::findOrFail($params[1]);
//
//            $user = User::findOrFail($chatId);
//
////                 Хотел заменить но перестали работать кнопки
////                $subject = DB::table('subjects')->find($params[1]);
////                $user = DB::table('users')->find($chatId);
//
//
//            if (!$user->is_work) {
//                $job = new ProcessPomodoroTimer($subject);
//                $pomodoro_time = $this->userInterface->getInfoAboutUser($chatId)->pomodoro_time;
//                dispatch($job)->delay(now()->addMinutes($pomodoro_time));
//                $answer = 'Помидор установлен';
//                $user->is_work = true;
//                $user->save();
//            } else {
//                $answer = 'Одновременно нельзя установить больше одного помидора';
//            }
//
//            $this->answerCallbackQuery($result, $answer);
//
//        }

        if ($params[0] == 'showTasks') {
            $this->showTaskForId($chatId, $params[1]);
        }

        if ($params[0] == 'buttonEditTaskId') {
            $buttons = $this->tasksInterface->getTasksForSubjectEdit($params[1]);
            $this->sendMessages($chatId, 'Список заданий:', $buttons);
        }

        if ($params[0] == 'deleteSubjectId') {
            $this->subjectInterface->deleteSubject($params[1]);
            $this->sendMessages($chatId, 'Категория удалена');
        }

        if ($params[0] == 'addSubjectId') {
            $this->setNextHandler($chatId, 'addSubject');
            $this->sendMessages($chatId, 'Напишите название категории');
        }

        if ($params[0] == 'editSubjectId') {
            $params = 'editSubject_' . $params[1];
            $this->setNextHandler($chatId, $params);
            $this->sendMessages($chatId, 'Напишите новое название категории');
        }

        if ($params[0] == 'addTaskId') {
            $params = 'addTask_' . $params[1];
            $this->setNextHandler($chatId, $params);
            $this->sendMessages($chatId, 'Напишите название задания');
        }

        if ($params[0] == 'deleteTaskId') {
            $this->tasksInterface->deleteTask($params[1]);
            $this->sendMessages($chatId, 'Задача удалена');
        }

        if ($params[0] == 'editTaskId') {
            $params = 'editTaskTitle_' . $params[1];
            $this->setNextHandler($chatId, $params);
            $this->sendMessages($chatId, 'Напишите новое название задания');
        }

        if ($params[0] == 'showTaskForId') {
            $content = $this->tasksInterface->showTask($params[1]);

            foreach ($content as $el) {
                $stringContent[0] = $el->title;
                $stringContent[1] = $el->content;
            }

            $buttons = $this->tasksInterface->getTaskForStart($params[1]);
            $this->sendMessages($chatId, " Название: $stringContent[0]" . PHP_EOL . "Описание:  $stringContent[1]", $buttons);
        }

        if ($params[0] == 'buttonEditSubjectId') {
            $subjects = $this->subjectInterface->getAllForUser($chatId);
            $answer = $this->subjectInterface->getAnswerAllSubjectEdit($subjects);
            $this->sendMessages($chatId, 'Предметы:', $answer);
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function editSubject($phrase, $chatId)
    {
        $this->subjectInterface->editSubject($phrase, $this->elementId);
        $this->sendMessages($chatId, 'Категория успешно изменена');
        $this->setNextHandler($chatId, null);
        $this->showAllSubject($chatId);
    }

    /**
     * @param $phrase
     * @param $chatId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addSubject($phrase, $chatId)
    {
        $this->subjectInterface->addSubject($phrase, $chatId);
        $this->sendMessages($chatId, 'Категория успешно добавлена');
        $this->setNextHandler($chatId, null);
        $this->showAllSubject($chatId);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addTask($phrase, $chatId)
    {
        $arrayTask = $this->tasksInterface->addTask($this->elementId, $phrase);
        $this->setNextHandler($chatId, "editTaskContent_" . $arrayTask["id"]);
        $this->sendMessages($chatId, 'Опишите задачу');
    }

    public function editTaskTitle($phrase, $chatId)
    {
        $this->tasksInterface->editTask(["title" => $phrase], $this->elementId);
        $this->setNextHandler($chatId, "editTaskContent_" . "$this->elementId");
        $this->sendMessages($chatId, 'Опишите задачу');
    }

    public function editTaskContent($phrase, $chatId)
    {
        $this->tasksInterface->editTask(["content" => $phrase], $this->elementId);
        $this->sendMessages($chatId, 'Выполнено');
        $this->setNextHandler($chatId, null);
    }

    /**
     * @param $chatId
     * @param $text
     * @param string $keyboard
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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

    /**
     * @param $callback_data
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function answerCallbackQuery($callback_data, $text)
    {
        $response = $this->client->request('GET', 'answerCallbackQuery', [
            'query' => [
                "callback_query_id" => $callback_data["id"],
                "text" => $text,
                "alert" => true
            ]
        ]);
    }

    /**
     * @param $chatId
     * @param $funcName
     */
    public function setNextHandler($chatId, $funcName): void
    {
        $this->predisClient->set($chatId, $funcName);
    }

    /**
     * @param $phrase
     * @param $chatId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getKT($phrase, $chatId)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = $this->parserKtInterface;
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtInterface->toStr($studentInfo));
            } catch (\Exception $ex) {
                $this->sendMessages($chatId, 'Что-то пошло не так. Попробуйте позже');
            } finally {
                $this->setNextHandler($chatId, null);
            }
        } else {
            $this->sendMessages($chatId, 'Некоректный номер зачетки' . PHP_EOL . 'Пожалуйста, повторите попытку.');
        }
    }

    /**
     * @param $phrase
     * @param $chatId
     * @param bool $isNeedToSave
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getMyKT($phrase, $chatId, $isNeedToSave = true)
    {
        if (ctype_digit($phrase)) {
            $studentInfo = $this->parserKtInterface;
            try {
                $studentInfo->getInfoAboutStudent($phrase);
                $this->sendMessages($chatId, $this->arrToKtInterface->toStr($studentInfo));

                if ($isNeedToSave) {
                    $this->predisClient->set('sn' . $chatId, $phrase);
                    $this->predisClient->set('si' . $chatId, serialize($studentInfo));
                    $this->setNextHandler($chatId, 'saveStudentNumber');
                    $this->sendMessages(
                        $chatId,
                        'Сохранить этот номер зачетки для последующих запросов?',
                        $this->keyBoardInterface->getKeyboardYesOrNo()
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

    /**
     * @param $phrase
     * @param $chatId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saveStudentNumber($phrase, $chatId)
    {
        if ($phrase == 'Да') {

            $studentNumber = $this->predisClient->get('sn' . $chatId);
            $studentInfo = unserialize($this->predisClient->get('si' . $chatId));
            $this->subjectInterface->saveSubjects($studentInfo, $chatId);
            $this->userInterface->saveStudentNumber($chatId, $studentNumber);

            $this->sendMessages(
                $chatId,
                'Успешно сохранено',
                $this->keyBoardInterface->getMainKeyboard()
            );
        } else {
            $this->sendMessages($chatId, 'Хорошо ;)', $this->keyBoardInterface->getMainKeyboard());
        }
        $this->setNextHandler($chatId, null);
    }

    /**
     * @param $chatId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function showAllSubject($chatId): void
    {
        $subjects = $this->subjectInterface->getAllForUser($chatId);
        $answer = $this->subjectInterface->getAnswerAllSubjectShow($subjects);
        $this->sendMessages($chatId, 'Предметы:', $answer);
    }

    public function showTaskForId($chatId, $subjectId): void
    {
        $buttons = $this->tasksInterface->getTasksForSubjectShow($subjectId);
        $this->sendMessages($chatId, 'Список заданий:', $buttons);
    }

}

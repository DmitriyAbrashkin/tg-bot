<?php


namespace App\Services;

use App\Jobs\ProcessPomodoroTimer;
use App\Models\Subject;
use App\Models\Task;
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
                    Log::channel('daily')->info(': ?????????????????? ???? : ' . $result['message']['from']['username'] . ' - ' . $phrase);
                    $fucName = $this->predisClient->get($chatId);

                    $params = explode("_", $fucName);
                    if (isset($params[1])) $this->elementId = $params[1];
                    else $this->elementId = "";
                    // $this->sendMessages($chatId, $this->elementId);
                    if ($phrase != '??????????' && $params[0] != null && method_exists($this, $params[0])) {
                        $this->{$params[0]}($phrase, $chatId); // ?????????? php
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
                                    '????????????',
                                    $this->keyBoardInterface->getMainKeyboard()
                                );

                                break;
                            case '?????? ????':
                                $user = $this->userInterface->getInfoAboutUser($chatId);
                                if ($user != null && $user->student_number != null) {
                                    $this->getMyKT($user->student_number, $chatId, false);
                                } else {
                                    $this->setNextHandler($chatId, 'getMyKT');
                                    $this->sendMessages($chatId, '???????????????? ?????????? ??????????????');
                                }
                                break;
                            case '????':
                                $this->setNextHandler($chatId, 'getKT');
                                $this->sendMessages($chatId, '???????????????? ?????????? ??????????????');
                                break;
                            case '????????????????':
                                $this->showAllSubject($chatId);
                                break;
                            case '????????????':
                                $this->sendMessages($chatId, 'https://telegra.ph/Kak-rabotat-s-botom-05-20');
                                break;
                            case '??????????????':
                                $this->statistics($chatId);
                                break;
                            case '???????????????? ?????????? ??????????????':
                                $this->setNextHandler($chatId, 'getMyKT');
                                $this->sendMessages($chatId, '???????????????? ?????????? ??????????????');
                                break;
                            case '???????????????? ?????????? ????????????????':
                                $this->setNextHandler($chatId, 'setNewPomodoroTimer');
                                $this->sendMessages($chatId, '???????????????? ?????????? ???????????? ?????????????? ?? ??????????????');
                                break;
                            case '??????????':
                                $this->setNextHandler($chatId, null);
                                $this->sendMessages($chatId, '?????? ?? ???????? ?????? ?????????????', $this->keyBoardInterface->getMainKeyboard());
                                break;
                            default:
                                $this->sendMessages($chatId, '?????????????????????? ??????????????');
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
            $this->sendMessages($chatId, '?????????????? ??????????????????');
        } else {
            $this->sendMessages($chatId, '???????????????????????? ??????????, ???????????????????? ?????????????? ?????????? ???????????? 0');
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
            '?????????? ???????????????? ' . $user->pomodoro_time . PHP_EOL .
            $result . PHP_EOL .
            '?????????? ?????????????????? ' . $allPomodoro . '????',
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

        switch ($params[0]) {
            case 'startPomodoroForId':
                $task = Task::findOrFail($params[1]);
                $subject = Subject::findOrFail($task->subject_id);
                $user = User::findOrFail($chatId);

                if (!$user->is_work) {
                    $job = new ProcessPomodoroTimer($subject);
                    $pomodoro_time = $this->userInterface->getInfoAboutUser($chatId)->pomodoro_time;
                    dispatch($job)->delay(now()->addMinutes($pomodoro_time));
                    $answer = '?????????????? ????????????????????';
                    $user->is_work = true;
                    $user->save();
                } else {
                    $answer = '???????????????????????? ???????????? ???????????????????? ???????????? ???????????? ????????????????';
                }

                $this->answerCallbackQuery($result, $answer);
                break;
            case 'showTasks':
                $this->showTaskForId($chatId, $params[1]);
                break;
            case 'buttonEditTaskId':
                $buttons = $this->tasksInterface->getTasksForSubjectEdit($params[1]);
                $this->sendMessages($chatId, '???????????? ??????????????:', $buttons);
                break;
            case 'deleteSubjectId':
                $this->subjectInterface->deleteSubject($params[1]);
                $this->sendMessages($chatId, '?????????????????? ??????????????');
                break;
            case 'addSubjectId':
                $this->setNextHandler($chatId, 'addSubject');
                $this->sendMessages($chatId, '???????????????? ???????????????? ??????????????????');
                break;
            case 'editSubjectId':
                $params = 'editSubject_' . $params[1];
                $this->setNextHandler($chatId, $params);
                $this->sendMessages($chatId, '???????????????? ?????????? ???????????????? ??????????????????');
                break;
            case 'addTaskId':
                $params = 'addTask_' . $params[1];
                $this->setNextHandler($chatId, $params);
                $this->sendMessages($chatId, '???????????????? ???????????????? ??????????????');
                break;
            case 'deleteTaskId':
                $this->tasksInterface->deleteTask($params[1]);
                $this->sendMessages($chatId, '???????????? ??????????????');
                break;
            case 'editTaskId':
                $params = 'editTaskTitle_' . $params[1];
                $this->setNextHandler($chatId, $params);
                $this->sendMessages($chatId, '???????????????? ?????????? ???????????????? ??????????????');
                break;
            case 'showTaskForId':
                $content = $this->tasksInterface->showTask($params[1]);

                foreach ($content as $el) {
                    $stringContent[0] = $el->title ?? '?????? ????????????????';
                    $stringContent[1] = $el->content ?? '';
                }

                $buttons = $this->tasksInterface->getTaskForStart($params[1]);
                $this->sendMessages($chatId, " ????????????????: $stringContent[0]" . PHP_EOL . "????????????????:  $stringContent[1]", $buttons);
                break;
            case 'buttonEditSubjectId':
                $subjects = $this->subjectInterface->getAllForUser($chatId);
                $answer = $this->subjectInterface->getAnswerAllSubjectEdit($subjects);
                $this->sendMessages($chatId, '????????????????:', $answer);
                break;
        }
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function editSubject($phrase, $chatId)
    {
        $this->subjectInterface->editSubject($phrase, $this->elementId);
        $this->sendMessages($chatId, '?????????????????? ?????????????? ????????????????');
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
        $this->sendMessages($chatId, '?????????????????? ?????????????? ??????????????????');
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
        $this->sendMessages($chatId, '?????????????? ????????????');
    }

    public function editTaskTitle($phrase, $chatId)
    {
        $this->tasksInterface->editTask(["title" => $phrase], $this->elementId);
        $this->setNextHandler($chatId, "editTaskContent_" . "$this->elementId");
        $this->sendMessages($chatId, '?????????????? ????????????');
    }

    public function editTaskContent($phrase, $chatId)
    {
        $this->tasksInterface->editTask(["content" => $phrase], $this->elementId);
        $this->sendMessages($chatId, '??????????????????');
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
                $this->sendMessages($chatId, '??????-???? ?????????? ???? ??????. ???????????????????? ??????????');
            } finally {
                $this->setNextHandler($chatId, null);
            }
        } else {
            $this->sendMessages($chatId, '?????????????????????? ?????????? ??????????????' . PHP_EOL . '????????????????????, ?????????????????? ??????????????.');
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
                        '?????????????????? ???????? ?????????? ?????????????? ?????? ?????????????????????? ?????????????????',
                        $this->keyBoardInterface->getKeyboardYesOrNo()
                    );


                } else {
                    $this->setNextHandler($chatId, null);
                }
            } catch (\Exception $exception) {
                $this->sendMessages($chatId, '??????-???? ?????????? ???? ??????. ???????????????????? ??????????');
                $this->setNextHandler($chatId, null);
            }
        } else {
            $this->sendMessages($chatId, '???????????????????????? ?????????? ??????????????' . PHP_EOL . '????????????????????, ?????????????????? ??????????????.');
        }
    }

    /**
     * @param $phrase
     * @param $chatId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saveStudentNumber($phrase, $chatId)
    {
        if ($phrase == '????') {

            $studentNumber = $this->predisClient->get('sn' . $chatId);
            $studentInfo = unserialize($this->predisClient->get('si' . $chatId));
            $this->subjectInterface->saveSubjects($studentInfo, $chatId);
            $this->userInterface->saveStudentNumber($chatId, $studentNumber);

            $this->sendMessages(
                $chatId,
                '?????????????? ??????????????????',
                $this->keyBoardInterface->getMainKeyboard()
            );
        } else {
            $this->sendMessages($chatId, '???????????? ;)', $this->keyBoardInterface->getMainKeyboard());
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
        $this->sendMessages($chatId, '????????????????:', $answer);
    }

    public function showTaskForId($chatId, $subjectId): void
    {
        $buttons = $this->tasksInterface->getTasksForSubjectShow($subjectId);
        $this->sendMessages($chatId, '???????????? ??????????????:', $buttons);
    }

}

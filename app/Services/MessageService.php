<?php


namespace App\Services;

use App\Jobs\ProcessPomodoroTimer;
use App\Models\Subject;
use App\Models\User;
use App\Services\Keyboard\Abstracts\KeyboardInterface;
use App\Services\ParserKT\Abstracts\ArrToStrKtInterface;
use App\Services\ParserKT\Abstracts\ParserKtInterface;
use App\Services\Subject\Abstracts\SubjectInterface;
use App\Services\Task\Abstracts\TaskInterface;
use App\Services\User\Abstracts\UserInterface;
use GuzzleHttp\Client;
use Illuminate\Queue\Jobs\Job;
use Predis\Client as Predis;
use Illuminate\Support\Facades\Log;

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
    private TaskInterface $tasksInterface;
    private KeyboardInterface $keyBoardInterface;
    private ParserKtInterface $parserKtInterface;

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
                    if ($fucName != null && method_exists($this, $fucName)) {
                        $this->{$fucName}($phrase, $chatId); // магия php
                    } else {
                        switch ($phrase) {
                            case '/start':

                                $this->userInterface->saveInfoAboutUser(
                                    $result['message']['from']['first_name'],
                                    $result['message']['from']['last_name'],
                                    $result['message']['from']['username'],
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
                            case 'Статистика':
                                $this->statistics($chatId);
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
            'Всего помидоров ' . $allPomodoro

        );
    }

    /**
     * @param $phrase
     * @param $chatId
     */
    public function addSubject($phrase, $chatId)
    {
        $this->subjectInterface->addSubject($phrase, $chatId);
        $this->sendMessages($chatId, 'Категория успешно добавлена');
        $this->showAllSubject($chatId);
        $this->setNextHandler($chatId, null);
    }

    /**
     * @param $chatId
     * @param $callback_data
     */
    public function actionInlineButtonSubjects($chatId, $callback_data, $result)
    {
        $params = explode("_", $callback_data);
        if ($params[0] = 'startPomodoroForId') {

            $subject = Subject::findOrFail($params[1]);

            $user = User::findOrFail($chatId);
            if(!$user->is_work){
                $job = (new ProcessPomodoroTimer($subject));
                $pomodoro_time = $this->userInterface->getInfoAboutUser($chatId)->pomodoro_time;
                dispatch($job)->delay(now()->addMinutes($pomodoro_time));
                $answer = 'Помидор установлен';
                $user->is_work = true;
                $user->save();
            }else{
                $answer = 'Одновременно нельзя установить больше одного помидора';
            }

            $this->answerCallbackQuery($result, $answer);

        }
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
            $this->sendMessages($chatId, 'Неккоректный номер зачетки' . PHP_EOL . 'Пожалуйста, повторите попытку.');
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
                $this->keyBoardSInterface->getMainKeyboard()
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
        $answer = $this->subjectInterface->getAnswerAllSubject($subjects);
        $this->sendMessages($chatId, 'Предметы:', $answer);
    }

}

<?php


namespace App\Services\Subject;


use App\Models\Subject;
use App\Services\ParserKT\ParserKtService;
use App\Services\Subject\Abstracts\SubjectInterface;

/**
 * Class SubjectService
 * @package App\Services
 */
class SubjectService implements SubjectInterface
{
    /**
     * @param $name
     * @param $chatId
     * @return mixed|void
     */
    public function addSubject($name, $chatId)
    {
        Subject::create([
            'name' => $name,
            'user_id' => $chatId
        ]);
    }

    /**
     * @param $id
     * @return Subject[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getAllForUser($id)
    {
        return Subject::all()->where('user_id', $id);
    }

    /**
     * @param ParserKtService $studentInfo
     * @param $chatId
     * @return mixed|void
     */
    public function saveSubjects(ParserKtService $studentInfo, $chatId)
    {
        foreach ($studentInfo->studyInfo as $item) {
            $this->addSubject($item->nameDiscipline, $chatId);
        }
    }

    /**
     * @param $subjects
     * @return false|mixed|string
     */
    public function getAnswerAllSubject($subjects)
    {
        $buttons = [];

        foreach ($subjects as $subject) {
            $buttons['inline_keyboard'][] = [

                [
                    "text" => $subject->name,
                    "callback_data" => "startPomodoroForId_" . $subject->id,
                ],
            ];
        }

        return json_encode($buttons, true);
    }
}

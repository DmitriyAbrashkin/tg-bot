<?php


namespace App\Services;


use App\Models\Subject;
use App\Services\ParserKT\ParserKtService;

class SubjectService
{

    public function addSubject($name, $chatId)
    {
        Subject::create([
            'name' => $name,
            'user_id' => $chatId
        ]);
    }

    public function getAllForUser($id)
    {
        return Subject::all()->where('user_id', $id);
    }

    public function saveSubjects(ParserKtService $studentInfo, $chatId)
    {
        foreach ($studentInfo->studyInfo as $item) {
            $this->addSubject($item->nameDiscipline, $chatId);
        }
    }


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

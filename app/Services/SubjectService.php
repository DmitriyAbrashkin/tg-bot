<?php


namespace App\Services;


use App\Models\Subject;

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

    public function getAnswerAllSubject($subjects)
    {
        $buttons = [];

        foreach ($subjects as $subject) {
            $buttons['inline_keyboard'][] = [

                [
                    "text" => $subject->name,
                    "callback_data" => "showTaskForSubjectId_" . $subject->id,
                ],

                [
                    "text" => "Удалить",
                    "callback_data" => "deleteSubjectId_" . $subject->id,
                ],

                [
                    "text" => 'Ред.',
                    "callback_data" => "editSubjectId_" . $subject->id,
                ]

            ];
        }


        var_dump(json_encode($buttons, true));
        return json_encode($buttons, true);
    }
}

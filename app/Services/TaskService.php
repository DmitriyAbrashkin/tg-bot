<?php


namespace App\Services;


use App\Models\Task;

class TaskService
{

    public function getTasksForSubject($id)
    {
        $tasks = Task::all()->where('subject_id', $id);

        $buttons = [];

        foreach ($tasks as $task) {
            $buttons['inline_keyboard'][] = [

                [
                    "text" => $task->content,
                    "callback_data" => "showTaskForSubjectId_" . $task->id,
                ],

                [
                    "text" => "Удалить",
                    "callback_data" => "deleteSubjectId_" . $task->id,
                ],
                [
                    "text" => 'Отредактировать',
                    "callback_data" => "editSubjectId_" . $task->id,
                ]

            ];
        }


        return json_encode($buttons, true);

    }

    public function saveTask($idSub, $content)
    {
        Task::create([
            'content' => $content,
            'subject_id' => $idSub
        ]);
    }


}

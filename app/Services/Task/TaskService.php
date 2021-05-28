<?php


namespace App\Services\Task;

use App\Models\Task;
use App\Services\Task\Abstracts\TaskInterface;

/**
 * Class TaskService
 * @package App\Services\Task
 */
class TaskService implements TaskInterface
{
    /**
     * @param $id
     * @return false|string
     */
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

    /**
     * @param $idSub
     * @param $content
     */
    public function saveTask($idSub, $content)
    {
        Task::create([
            'content' => $content,
            'subject_id' => $idSub
        ]);
    }


}

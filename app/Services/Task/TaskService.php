<?php


namespace App\Services\Task;

use App\Models\Task;
use App\Services\Task\Abstracts\TaskInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class TaskService
 * @package App\Services\Task
 */
class TaskService implements TaskInterface
{
    /**
     * @param $id
     * @return mixed|void
     */
    public function showTask($id)
    {
       return DB::table("tasks")->where('id', $id)->get("content")->values();
    }

    /**
     * @param $id
     * @return mixed|void
     */
    public function deleteTask($id)
    {
        DB::table("tasks")->delete($id);
    }

    /**
     * @param $content
     * @param $id
     * @return mixed|void
     */
    public function editTask($content, $id)
    {
        Task::find($id)->update(['content' => $content]);
    }

    /**
     * @param $id
     * @return false|string
     */
    public function getTasksForSubject($id)
    {
        $tasks = Task::all()->where('subject_id', $id);

        $buttons['inline_keyboard'][] = [
            [
                "text" => "Добавить",
                "callback_data" => "addTaskId_" . $id,
            ]
        ];

        foreach ($tasks as $task) {
            $buttons['inline_keyboard'][] = [

                [
                    "text" => $task->content,
                    "callback_data" => "showTaskId_" . $task->id,
                ],

                [
                    "text" => "Удалить",
                    "callback_data" => "deleteTaskId_" . $task->id,
                ],
                [
                    "text" => 'Отредактировать',
                    "callback_data" => "editTaskId_" . $task->id,
                ]

            ];
        }
        return json_encode($buttons, true);
    }



    public function getTaskForStart($id)
    {
        $buttons['inline_keyboard'][] = [
            [
                "text" => "Начать",
                "callback_data" => "startTaskId_" . $id,
            ]
        ];

        return json_encode($buttons, true);
    }

    /**
     * @param $idSub
     * @param $content
     */
    public function addTask($idSub, $content)
    {
        Task::create([
            'content' => $content,
            'subject_id' => $idSub
        ]);
    }

}

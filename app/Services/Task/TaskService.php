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
        return DB::table("tasks")->where('id', $id)->get()->values();
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
     * @param array $data
     * @param $id
     * @return mixed|void
     */
    public function editTask(array $data, $id)
    {
        Task::find($id)->update($data);
    }

    /**
     * @param $id
     * @return false|string
     */
    public function getTasksForSubjectShow($id)
    {
        $tasks = Task::all()->where('subject_id', $id);

        $buttons['inline_keyboard'][] = [
            [
                "text" => "Добавить",
                "callback_data" => "addTaskId_" . $id,
            ],
            [
                "text" => "Изменить или удалить",
                "callback_data" => "buttonEditTaskId_" . $id,
            ]
        ];

        foreach ($tasks as $task) {
            $buttons['inline_keyboard'][] = [
                [
                    "text" => $task->title,
                    "callback_data" => "showTaskForId_" . $task->id,
                ]
            ];
        }
        return json_encode($buttons, true);
    }

    public function getTasksForSubjectEdit($id)
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
                    "text" => $task->title,
                    "callback_data" => "showTaskForId_" . $task->id,
                ],
                [
                    "text" => '✏',
                    "callback_data" => "editTaskId_" . $task->id,
                ],
                [
                    "text" => "🚫",
                    "callback_data" => "deleteTaskId_" . $task->id,
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
                "callback_data" => "startPomodoroForId_" . $id,
            ]
        ];

        return json_encode($buttons, true);
    }

    /**
     * @param $idSub
     * @param $title
     * @return mixed
     */
    public function addTask($idSub, $title)
    {
        return Task::create([
            'title' => $title,
            'subject_id' => $idSub
        ]);
    }

}

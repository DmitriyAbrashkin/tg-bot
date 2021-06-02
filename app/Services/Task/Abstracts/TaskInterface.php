<?php

namespace App\Services\Task\Abstracts;


/**
 * Interface AuthInterface
 * @package App\Services\Abstracts
 */
interface TaskInterface
{
    /**
     * @param $id
     * @return mixed
     */
    public function getTasksForSubjectShow($id);

    /**
     * @param $id
     * @return mixed
     */
    public function getTasksForSubjectEdit($id);

    /**
     * @param $id
     * @return mixed
     */
    public function getTaskForStart($id);

    /**
     * @param $id
     * @return mixed
     */
    public function showTask($id);

    /**
     * @param $id
     * @return mixed
     */
    public function deleteTask($id);

    /**
     * @param array $data
     * @param $id
     * @return mixed
     */
    public function editTask(array $data, $id);

    /**
     * @param $idSub
     * @param $content
     * @return mixed
     */
    public function addTask($idSub, $title);

}

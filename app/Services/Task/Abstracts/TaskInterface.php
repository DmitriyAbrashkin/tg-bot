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
    public function getTasksForSubject($id);

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
     * @param $content
     * @param $id
     * @return mixed
     */
    public function editTask($content, $id);

    /**
     * @param $idSub
     * @param $content
     * @return mixed
     */
    public function addTask($idSub, $content);

}

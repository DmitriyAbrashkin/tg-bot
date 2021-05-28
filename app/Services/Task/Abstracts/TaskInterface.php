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
     * @param $idSub
     * @param $content
     * @return mixed
     */
    public function saveTask($idSub, $content);

}

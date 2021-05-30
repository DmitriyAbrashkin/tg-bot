<?php

namespace App\Services\User\Abstracts;


/**
 * Interface UserInterface
 * @package App\Services\User\Abstracts
 */

interface UserInterface
{
    /**
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $tgId
     * @return mixed
     */
    public function saveInfoAboutUser($firstName, $lastName, $userName, $tgId);

    /**
     * @param $tgId
     * @param $studentNumber
     * @return mixed
     */
    public function saveStudentNumber($tgId, $studentNumber);

    /**
     * @param $tgId
     * @return mixed
     */
    public function getInfoAboutUser($tgId);


    /**
     * @param $user_id
     * @param $time
     * @return mixed
     */
    public function setNewPomodoroTimer($user_id, $time);

}

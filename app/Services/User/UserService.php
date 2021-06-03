<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\User\Abstracts\UserInterface;

/**
 * Class UserService
 * @package App\Services\User
 */
class UserService implements UserInterface
{
    /**
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $tgId
     * @return mixed|void
     */
    public function saveInfoAboutUser($firstName, $lastName, $userName, $tgId)
    {
        $user = $this->getInfoAboutUser($tgId);
        if ($user == null) {
            User::create([
                'id' => $tgId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $userName,
            ]);
        } else {
            User::firstWhere('id', $tgId)->update(
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $userName,
                ]);
        }

    }

    /**
     * @param $tgId
     * @param $studentNumber
     * @return mixed|void
     */
    public function saveStudentNumber($tgId, $studentNumber)
    {
        $user = $this->getInfoAboutUser($tgId);
        $user->student_number = $studentNumber;
        $user->save();
    }

    /**
     * @param $tgId
     * @return mixed
     */
    public function getInfoAboutUser($tgId)
    {
        return User::find($tgId);
    }



    /**
     * @param $user_id
     * @param $time
     * @return mixed
     */
    public function setNewPomodoroTimer($user_id, $time)
    {
        $user = $this->getInfoAboutUser($user_id);
        $user->pomodoro_time = $time;
        $user->save();
    }
}

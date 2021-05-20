<?php


namespace App\Services;

use App\Models\User;

class UserService
{

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

    public function saveStudentNumber($tgId, $studentNumber)
    {
        $user = $this->getInfoAboutUser($tgId);
        $user->student_number = $studentNumber;
        $user->save();
    }

    public function getInfoAboutUser($tgId)
    {
        return User::find($tgId);
    }


}

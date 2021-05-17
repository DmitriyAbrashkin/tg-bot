<?php


namespace App\Services;

use App\Models\Subject;
use App\Models\User;

class PomodoroService
{
    private $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    public function checkPomodoro(Subject $subject){
            $this->messageService->sendMessages($subject->user_id, 'Ваш помидор истек, вам начисленна 1 монетка. Так держать!');

            $user = User::find($subject->user_id);
            $user->count_money += 1;
            $user->save();

            $subject->death_time = null;
            $subject->save();
    }
}

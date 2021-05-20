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

    public function checkPomodoro(Subject $subject)
    {
        $this->messageService->sendMessages($subject->user_id, 'Ваш помидор истек, вам начисленна 1 монетка. Так держать!');
        $subject->count_pomodoro += 1;
        $subject->save();
    }
}

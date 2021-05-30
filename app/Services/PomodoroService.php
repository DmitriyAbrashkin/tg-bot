<?php


namespace App\Services;

use App\Models\Subject;
use App\Models\User;

class PomodoroService
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function checkPomodoro(Subject $subject)
    {
        $this->messageService->sendMessages($subject->user_id, 'Ваш помидор истек, вам начисленна 1 монетка. Так держать!');
        $subject->count_pomodoro += 1;
        $subject->save();

        $user = User::findOrFail($subject->user_id);
        $user->is_work = false;
        $user->save();
    }
}

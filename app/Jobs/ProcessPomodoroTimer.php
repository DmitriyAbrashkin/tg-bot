<?php

namespace App\Jobs;

use App\Models\Subject;
use App\Services\PomodoroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPomodoroTimer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subject;

    /**
     * ProcessPomodoroTimer constructor.
     * @param $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    public function handle(PomodoroService $pomodoroService)
    {
        $pomodoroService->checkPomodoro($this->subject);
    }
}

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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected  $subject;
    protected $pomodoroService;

    public function __construct(Subject $subject)
    {
        $this->subject = $subject;
    }

    /**
     * Execute the job.
     *
     * @param Subject $subject
     * @return void
     */
    public function handle()
    {
        $this->pomodoroService = new PomodoroService();
        $this->pomodoroService->checkPomodoro($this->subject);
    }
}

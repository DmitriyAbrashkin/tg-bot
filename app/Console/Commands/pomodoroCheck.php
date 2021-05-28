<?php


namespace App\Console\Commands;


use App\Jobs\ProcessPomodoroTimer;
use App\Services\PomodoroService;
use Illuminate\Console\Command;

class pomodoroCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:pomodoro';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверка истекших помидоров, отправка сообщений об этом';

    private $pomodoroService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PomodoroService $pomodoroService)
    {
        parent::__construct();
        $this->pomodoroService = $pomodoroService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}

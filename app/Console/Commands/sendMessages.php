<?php

namespace App\Console\Commands;

use App\Services\MessageService;
use Illuminate\Console\Command;

class sendMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:messages';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка сообщений';
    private $messageService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->messageService->sendMessages();
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MessageService;

class getUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получение новых сообщений';

    private $messagesService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MessageService $messagesService)
    {
        parent::__construct();
        $this->messagesService = $messagesService;
    }

    /**
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */

    public function handle()
    {
        $this->messagesService->getUpdates();
        return 0;
    }
}

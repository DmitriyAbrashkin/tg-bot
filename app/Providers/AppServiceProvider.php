<?php

namespace App\Providers;

use App\Services\Keyboard\Abstracts\KeyboardInterface;
use App\Services\KeyBoard\KeyboardService;
use App\Services\ParserKT\Abstracts\ArrToStrKtInterface;
use App\Services\ParserKT\Abstracts\ParserKtInterface;
use App\Services\ParserKT\ArrToStrKtService;
use App\Services\ParserKT\ParserKtService;
use App\Services\Subject\Abstracts\SubjectInterface;
use App\Services\Subject\SubjectService;
use App\Services\Task\Abstracts\TaskInterface;
use App\Services\Task\TaskService;
use App\Services\User\Abstracts\UserInterface;
use App\Services\User\UserService;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{


    protected $services = [
        KeyboardInterface::class => KeyboardService::class,
        ArrToStrKtInterface::class => ArrToStrKtService::class,
        SubjectInterface::class => SubjectService::class,
        TaskInterface::class => TaskService::class,
        UserInterface::class => UserService::class,
        ParserKtInterface::class => ParserKtService::class
    ];
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind('App\Services\Keyboard\Abstracts\KeyboardInterface', "App\Services\KeyBoard\KeyboardService");

        foreach ($this->services as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

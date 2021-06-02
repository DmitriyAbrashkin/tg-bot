<?php

namespace Tests\Feature;

use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {

    }
}

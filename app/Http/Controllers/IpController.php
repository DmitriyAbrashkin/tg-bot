<?php

namespace App\Http\Controllers;

use App\Services\IpService;
use Illuminate\Http\Request;

class IpController extends Controller
{
    private $ipService;

    public function __construct(IpService $ipService)
    {
        $this->ipService = $ipService;
    }

    public function showIp(Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $result = $this->ipService->checkIp($ip);
        return response("Ваш ip: " . $_SERVER['REMOTE_ADDR'] . "   IP    " . $result, 200);
    }
}

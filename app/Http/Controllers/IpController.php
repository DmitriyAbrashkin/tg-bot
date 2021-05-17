<?php

namespace App\Http\Controllers;

use App\Services\ParserKT\ArrToStrKtService;
use App\Services\ParserKT\IpService;
use App\Services\SubjectService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Services\ParserKT\ParserKtService;

class IpController extends Controller
{
    private $ipService;
    private $arrToKtService;

    public function __construct(ArrToStrKtService $arrToKtService)
    {
        $this->arrToKtService = $arrToKtService;
    }

    public function showIp(Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $result = $this->ipService->checkIp($ip);
        return response("Ваш ip: " . $_SERVER['REMOTE_ADDR'] . "   IP    " . $result, 200);
    }

    public function getKt($number)
    {

         $studentInfo = new ParserKtService();
         $studentInfo->getInfoAboutStudent($number);
         $this->arrToKtService->toStr($studentInfo);
         $subjectService = new SubjectService();
         $subjectService->saveSubjects($studentInfo, 436545935);

         return response()->json($studentInfo, 200, array('Content-Type' => 'application/json;charset=utf8'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


}

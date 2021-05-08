<?php

namespace App\Http\Controllers;

use App\Services\ArrToStrKtService;
use App\Services\IpService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Services\ParserKtService;

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

        /* $studentInfo = new ParserKtService();
         $studentInfo->getInfoAboutStudent($number);
         $this->arrToKtService->toStr($studentInfo);
         $studentInfo->saveInfoAboutStudent(1);*/

        /* $this->client = new Client(
             ['base_uri' => 'https://portal.kuzstu.ru/api/groups']
         );

         $response = $this->client->request('GET');
         $messages = json_decode($response->getBody()->getContents(), true);
         dd($messages);*/
        $fucName = 'test';

        dd(__METHOD__);


        return response()->json($studentInfo, 200, array('Content-Type' => 'application/json;charset=utf8'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


}

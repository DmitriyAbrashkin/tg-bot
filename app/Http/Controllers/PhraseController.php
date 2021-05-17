<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhraseRequest;
use App\Services\ParserKT\ParserKT\ParserKT\PhraseService;


class PhraseController extends Controller
{
    private $phaseService;

    public function __construct(PhraseService $phaseService)
    {
        $this->phaseService = $phaseService;
    }

    public function isPalindrome(PhraseRequest $request)
    {
        $result = $this->phaseService->isPalindrome($request->phrase);
        

        return response($result, 200);
    }
}

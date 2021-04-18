<?php


namespace App\Services;


class PhraseService
{
    public function isPalindrome($phrase): string
    {
        $temp = strtolower($phrase);
        $revers = strrev($temp);
        $revers == $temp ? $result = " - это фраза палиндром" : $result = " - эта фраза не палиндром";
        return $phrase . $result;
    }
}

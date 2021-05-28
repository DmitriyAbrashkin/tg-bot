<?php

namespace App\Services\ParserKT\Abstracts;


/**
 * Interface AuthInterface
 * @package App\Services\Abstracts
 */
interface ParserKtInterface
{
    /**
     * @param $number
     * @return mixed
     */
    public function getInfoAboutStudent($number);

}

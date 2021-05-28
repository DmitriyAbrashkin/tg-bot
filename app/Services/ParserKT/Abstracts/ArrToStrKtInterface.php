<?php

namespace App\Services\ParserKT\Abstracts;


/**
 * Interface AuthInterface
 * @package App\Services\Abstracts
 */
interface ArrToStrKtInterface
{
    /**
     * @param $arr
     * @return mixed
     */
    public function toStr($arr);

    /**
     * @param $item
     * @param string $resKt
     * @param string $resProp
     * @return array
     */
    public function getInfoSubject($item, string $resKt, string $resProp): array;

}

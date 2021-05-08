<?php


namespace App\Services;


class ArrToStrKtService
{

    public function toStr($arr)
    {
        $result = 'Текущая успеваемость для: ' . $arr->nameStudent . PHP_EOL . PHP_EOL;
        foreach ($arr->studyInfo as $item) {
            $resKt = '';
            $resProp = '';
            list($resKt, $resProp) = $this->getInfoSubject($item, $resKt, $resProp);

            $result .= PHP_EOL . $item->nameDiscipline
                . PHP_EOL
                . PHP_EOL
                . 'Контрольные точки'
                . PHP_EOL
                . $resKt
                . PHP_EOL
                . 'Пропуски'
                . PHP_EOL
                . $resProp
                . PHP_EOL;

        }

        return $result;
    }


    public function getInfoSubject($item, string $resKt, string $resProp): array
    {
        foreach ($item->ktResult as $kt)
            $resKt .= $kt . ' ';
        foreach ($item->prop as $prop)
            $resProp .= $prop . '   ';
        return array($resKt, $resProp);
    }

}

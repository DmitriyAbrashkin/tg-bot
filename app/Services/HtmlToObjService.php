<?php


namespace App\Services;


class HtmlToObjService
{
    public $nameDiscipline;
    public $ktResult;
    public $prop;

    public function get_kt_from_string($items): HtmlToObjService
    {
        $studyInfo = new HtmlToObjService();
        $studyInfo->nameDiscipline = '';
        $studyInfo->ktResult = [null, null, null, null];
        $studyInfo->prop = [null, null, null, null];

        $res = array('Не зачет', 'Зачет', 'Нет допуска');
        $items = str_replace($res, '', $items);
        $str = explode(' ', $items);
        foreach ($str as $item) {
            if (!ctype_digit($item)) {
                $studyInfo->nameDiscipline .= $item . ' ';
            }
        }

        $studyInfo->nameDiscipline = trim($studyInfo->nameDiscipline);
        $kt = explode(' ', str_replace(trim($studyInfo->nameDiscipline), '', $items));
        $studyInfo->nameDiscipline = mb_convert_encoding( $studyInfo->nameDiscipline , "utf-8");
        $j = 0;
        $k = 0;
        for ($i = 1; $i < count($kt) - 1; $i++) {
            if ($i % 2 == 0) {
                $studyInfo->ktResult[$j] = $kt[$i];
                $j++;
            } else {
                $studyInfo->prop[$k] = $kt[$i];
                $k++;
            }
        }


        return $studyInfo;
    }
}

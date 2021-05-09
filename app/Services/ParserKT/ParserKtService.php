<?php

namespace App\Services\ParserKT;


use App\Models\Subject;
use App\Models\SubjectStat;
use DiDom\Document;
use App\Services\ParserKT\HtmlToObjService;

class ParserKtService
{
    public $nameStudent;
    public $number;
    public $studyInfo;


    public function getInfoAboutStudent($number)
    {
        $this->number = $number;

        $studyInfo = new HtmlToObjService();

        $site_name = "https://portal.kuzstu.ru/learning/progress/current/report/cp3?search_str=" . $this->number . "&is_filters_enabled=1";

        $document = new Document($site_name, true);
        //  if($document->find("#content > div > div > div.callout.callout-danger > h4 > i")[0]->text() != null) return "";

        $this->nameStudent = $document->find("#content > div > div:nth-child(3) > div.callout.callout-info > div:nth-child(2) > h4 > b")[0]->text();
        $table = $document->find('*[^data-term_id=29]');
        for ($i = 0; $i < count($table); $i++) {
            $item = $table[$i]->text();
            if (stristr($item, 'семестр')) break;
            $this->studyInfo[] = $studyInfo->get_kt_from_string(trim(preg_replace('|\s+|', ' ', $item)));
        }
    }
}

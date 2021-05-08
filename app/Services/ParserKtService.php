<?php

namespace App\Services;


use App\Models\Subject;
use App\Models\SubjectStat;
use DiDom\Document;
use App\Services\HtmlToObjService;

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

    public function saveInfoAboutStudent($id)
    {

        foreach ($this->studyInfo as $item) {

            $subId = Subject::where('name', $item->nameDiscipline)->where('user_id', $id)->first();
            if ($subId == null) {

                $subjectStat = SubjectStat::create(
                    [
                        '1kt' => $item->ktResult[0],
                        '2kt' => $item->ktResult[1],
                        '3kt' => $item->ktResult[2],
                        '4kt' => $item->ktResult[3],
                        '1pr' => $item->prop[0],
                        '2pr' => $item->prop[1],
                        '3pr' => $item->prop[2],
                        '4pr' => $item->prop[3],
                    ]);


                $subject = Subject::create([
                    'name' => $item->nameDiscipline,
                    'user_id' => $id,
                    'id_stats_subject' => $subjectStat->id,

                ]);
            } else {

                $subjectStat = SubjectStat::find($subId->id_stats_subject)->update(
                    [
                        '1kt' => $item->ktResult[0],
                        '2kt' => $item->ktResult[1],
                        '3kt' => $item->ktResult[2],
                        '4kt' => $item->ktResult[3],
                        '1pr' => $item->prop[0],
                        '2pr' => $item->prop[1],
                        '3pr' => $item->prop[2],
                        '4pr' => $item->prop[3],
                    ]);
            }
        }
    }
}

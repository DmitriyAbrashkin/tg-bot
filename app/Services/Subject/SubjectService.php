<?php


namespace App\Services\Subject;


use App\Models\Subject;
use App\Services\ParserKT\ParserKtService;
use App\Services\Subject\Abstracts\SubjectInterface;
use Illuminate\Support\Facades\DB;

/**
 * Class SubjectService
 * @package App\Services
 */
class SubjectService implements SubjectInterface
{

    /**
     * @param $name
     * @param $chatId
     * @return mixed|void
     */
    public function addSubject($name, $chatId)
    {
        Subject::create([
            'name' => $name,
            'user_id' => $chatId
        ]);
    }

    /**
     * @param $id
     */
    public function deleteSubject($id)
    {
        DB::table("subjects")->delete($id);
    }

    /**
     * @param $id
     * @return Subject[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getAllForUser($id)
    {
        return Subject::all()->where('user_id', $id);
    }

    /**
     * @param ParserKtService $studentInfo
     * @param $chatId
     * @return mixed|void
     */
    public function saveSubjects(ParserKtService $studentInfo, $chatId)
    {
        Subject::where('user_id', $chatId)->delete();
        foreach ($studentInfo->studyInfo as $item) {
            $this->addSubject($item->nameDiscipline, $chatId);
        }
    }

    public function clearSubjects($chatId)
    {
        $this->getAllForUser($chatId)->delete();
    }

    /**
     * @param $name
     * @param $id
     * @return mixed|void
     */
    public function editSubject($name, $id){
       Subject::find($id)->update(['name' => $name]);
    }

    /**
     * @param $subjects
     * @return false|mixed|string
     */
    public function getAnswerAllSubject($subjects)
    {
        $buttons['inline_keyboard'][] = [
            [
                "text" => "Добавить",
                "callback_data" => "addSubjectId_",
            ]
        ];

        foreach ($subjects as $subject) {
            $buttons['inline_keyboard'][] = [

                [
                    "text" => $subject->name,
                    "callback_data" => "startPomodoroForId_" . $subject->id,
                ],
                [
                    "text" => "Удалить",
                    "callback_data" => "deleteSubjectId_" . $subject->id,
                ],
                [
                    "text" => 'Отредактировать',
                    "callback_data" => "editSubjectId_" . $subject->id,
                ]
            ];
        }
        return json_encode($buttons, true);
    }
}

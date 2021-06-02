<?php

namespace App\Services\Subject\Abstracts;

use App\Services\ParserKT\ParserKtService;

/**
 * Interface AuthInterface
 * @package App\Services\Abstracts
 */
interface SubjectInterface
{
    /**
     * @param $name
     * @param $chatId
     * @return mixed
     */
    public function addSubject($name, $chatId);

    /**
     * @param $name
     * @param $id
     * @return mixed
     */
    public function editSubject($name, $id);

    /**
     * @param $id
     * @return mixed
     */
    public function getAllForUser($id);

    /**
     * @param ParserKtService $studentInfo
     * @param $chatId
     * @return mixed
     */
    public function saveSubjects(ParserKtService $studentInfo, $chatId);

    /**
     * @param $id
     * @return mixed
     */
    public function deleteSubject($id);

    /**
     * @param $chatId
     * @return mixed
     */
    public function clearSubjects($chatId);

    /**
     * @param $subjects
     * @return mixed
     */
    public function getAnswerAllSubjectShow($subjects);

    /**
     * @param $subjects
     * @return mixed
     */
    public function getAnswerAllSubjectEdit($subjects);

}

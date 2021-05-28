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
     * @param $subjects
     * @return mixed
     */
    public function getAnswerAllSubject($subjects);

}

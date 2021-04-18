<?php


namespace App\Services;


class IpService
{
    public function checkIp($ip): string
    {
        if (($ip >= 167772160 && $ip <= 184549375) || ($ip >= 2886729728 && $ip <= 2887778303) || ($ip >= 3232235520 && $ip <= 3232301055))
            $result = "Серый";
        else
            $result = "Белый";
        return $result;
    }

}

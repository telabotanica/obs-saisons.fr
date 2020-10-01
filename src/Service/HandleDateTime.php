<?php

namespace App\Service;

use IntlDateFormatter;

class HandleDateTime
{
    public function dateTransFormat(string $pattern, \DateTimeInterface $dateTime)
    {
        $fmt = $this->fmtCreate($pattern);

        return datefmt_format($fmt, $dateTime);
    }

    private function fmtCreate(string $pattern): IntlDateFormatter
    {
        return datefmt_create(
            'fr_FR',
            null,
            null,
            null,
            null,
            $pattern
        );
    }

    public function browserSupportDate(string $date)
    {
        if (preg_match('/^([\d]{2}\/){2}[\d]{4}$/', $date)) {
            $frDateArray = array_reverse(explode('/', $date));
            $date = implode('-', $frDateArray);
        }

        return $date;
    }
}

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

    public function fmtCreate(string $pattern): IntlDateFormatter
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
}

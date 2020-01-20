<?php
// src/Twig/AppExtension.php

namespace App\Twig;

use IntlDateFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class AppExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('eventDatesDisplay', [$this, 'displayEventDates']),
        ];
    }

    /**
     * @return string
     */
    public function displayEventDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $separator = '-')
    {
        $startDateSplit = explode('-', $startDate->format('Y-m-d'));
        $endDateSplit = explode('-', $endDate->format('Y-m-d'));
        $pattern = 'd MMMM Y';
        $patternArray = array_reverse(explode(' ', $pattern));
        $fmt = $this->fmtCreate($pattern);
        $displayedEndDate = datefmt_format($fmt, $endDate);

        if ($startDateSplit === $endDateSplit) {
            return $displayedEndDate;
        }

        foreach ($startDateSplit as $key => $date) {
            if ($endDateSplit[$key] != $date) {
                $pattern = implode(' ', array_reverse(array_slice($patternArray, $key)));
                //die(dump($pattern));
                break;
            }
        }
        $fmt = $this->fmtCreate($pattern);

        return datefmt_format($fmt, $startDate).' '.$separator.' '.$displayedEndDate;
    }

    /**
     * @return IntlDateFormatter
     */
    private function fmtCreate(string $pattern)
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

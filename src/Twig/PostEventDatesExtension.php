<?php

namespace App\Twig;

use App\Service\HandleDateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class PostEventDatesExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('eventDatesDisplay', [
                $this,
                'displayEventDates',
            ]),
        ];
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('shortDate', [
                $this,
                'displayEventShortDates',
            ]),
        ];
    }

    public function displayEventDates(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $separator = '-'
    ): string {
        $startDateSplit = explode(
            '-',
            $startDate->format('Y-m-d')
        );
        $endDateSplit = explode(
            '-',
            $endDate->format('Y-m-d')
        );
        $pattern = 'd MMMM Y';
        $patternArray = explode(' ', $pattern);
        $patternArray = array_reverse($patternArray);
        $transDateTime = new HandleDateTime();
        $displayedEndDate = $transDateTime->dateTransFormat($pattern, $endDate);
        if ($startDateSplit === $endDateSplit) {
            return $displayedEndDate;
        }

        foreach ($startDateSplit as $key => $date) {
            if ($endDateSplit[$key] != $date) {
                $pattern = implode(
                    ' ',
                    array_reverse(
                        array_slice($patternArray, $key)
                    )
                );

                break;
            }
        }

        return $transDateTime->dateTransFormat($pattern, $startDate).' '.$separator.' '.$displayedEndDate;
    }

    public function displayEventShortDates(
        \DateTimeInterface $date
    ): string {
        $pattern = 'd-MMM';
        if (date('Y') !== $date->format('Y')) {
            $pattern .= '-Y';
        }
        $translatedShortDate = (new HandleDateTime())->dateTransFormat($pattern, $date);

        return str_replace('-', '<br>', $translatedShortDate);
    }
}

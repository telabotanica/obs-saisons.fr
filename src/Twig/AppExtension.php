<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use App\Service\SlugGenerator;
use IntlDateFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

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
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
            new TwigFilter('arrayUnique', [$this, 'arrayUnique']),
        ];
    }

    public function slugify(string $string): string
    {
        $slugGenerator = new SlugGenerator();

        return $slugGenerator->slugify($string);
    }

    public function arrayUnique(array $array): array
    {
        $arrayUnique = [];
        foreach ($array as $value) {
            if (!in_array($value, $arrayUnique)) {
                $arrayUnique[] = $value;
            }
        }

        return $arrayUnique;
    }

    public function displayEventDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $separator = '-'): string
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
}

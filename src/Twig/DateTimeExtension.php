<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateTimeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('time_diff', [$this, 'timeDiff']),
        ];
    }

    public function timeDiff(?\DateTimeInterface $date): string
    {
        if (!$date) {
            return 'never';
        }

        $now = new \DateTime();
        $diff = $now->diff($date);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }

        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }

        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        }

        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        }

        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }

        return 'just now';
    }
}
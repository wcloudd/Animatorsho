<?php

namespace App\Support;

class SafeStoryTextFormatter
{
    public static function sanitize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $sanitized = trim(strip_tags($text));

        return $sanitized === '' ? null : $sanitized;
    }

    public static function toHtml(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);

        $lines = preg_split("/\r\n|\n|\r/", $escaped) ?: [];
        $html = [];
        $inList = false;
        $listType = null;

        foreach ($lines as $line) {
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches) === 1) {
                if (! $inList || $listType !== 'ul') {
                    if ($inList) {
                        $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    }

                    $html[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }

                $html[] = '<li>'.$matches[1].'</li>';

                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches) === 1) {
                if (! $inList || $listType !== 'ol') {
                    if ($inList) {
                        $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    }

                    $html[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }

                $html[] = '<li>'.$matches[1].'</li>';

                continue;
            }

            if ($inList) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $inList = false;
                $listType = null;
            }

            if ($line === '') {
                $html[] = '<br>';

                continue;
            }

            $html[] = '<p>'.$line.'</p>';
        }

        if ($inList) {
            $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
        }

        return implode('', $html);
    }

    public static function toPreview(?string $text, int $maxLength = 120): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $plain = (string) preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
        $plain = (string) preg_replace('/^[-*]\s+/m', '', $plain);
        $plain = (string) preg_replace('/^\d+\.\s+/m', '', $plain);
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));

        if ($plain === '') {
            return '';
        }

        if (mb_strlen($plain) <= $maxLength) {
            return $plain;
        }

        return mb_substr($plain, 0, $maxLength).'…';
    }
}

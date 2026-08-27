<?php

namespace App\Services;

class VietnameseCurrencyReader
{
    private static array $digits = [
        0 => 'không',
        1 => 'một',
        2 => 'hai',
        3 => 'ba',
        4 => 'bốn',
        5 => 'năm',
        6 => 'sáu',
        7 => 'bảy',
        8 => 'tám',
        9 => 'chín',
    ];

    private static array $units = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];

    /**
     * Convert numeric amount to Vietnamese currency in words.
     */
    public static function convert(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return 'Không đồng';
        }

        $numeric = (float) str_replace([',', ' ', 'đ', 'VND', 'vnd'], '', (string) $amount);
        $numeric = round($numeric);

        if ($numeric == 0) {
            return 'Không đồng';
        }

        $isNegative = $numeric < 0;
        $numeric = abs($numeric);

        $numberStr = (string) $numeric;
        $length = strlen($numberStr);

        $groups = [];
        while ($length > 0) {
            $take = min(3, $length);
            $groups[] = substr($numberStr, max(0, $length - 3), $take);
            $length -= 3;
        }

        $resultParts = [];
        $totalGroups = count($groups);

        for ($i = $totalGroups - 1; $i >= 0; $i--) {
            $groupVal = (int) $groups[$i];
            if ($groupVal > 0 || $i == 0 && empty($resultParts)) {
                $groupText = self::readThreeDigits($groups[$i], $i < $totalGroups - 1);
                if (! empty($groupText)) {
                    $unit = self::$units[$i] ?? '';
                    $resultParts[] = trim($groupText.' '.$unit);
                }
            }
        }

        $text = trim(implode(' ', $resultParts));
        $text = preg_replace('/\s+/', ' ', $text);

        // Capitalize first character and append "đồng chẵn"
        $final = mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1).' đồng chẵn';

        return ($isNegative ? 'Âm ' : '').$final;
    }

    private static function readThreeDigits(string $groupStr, bool $hasHigherGroups): string
    {
        $groupStr = str_pad($groupStr, 3, '0', STR_PAD_LEFT);
        $h = (int) $groupStr[0];
        $t = (int) $groupStr[1];
        $u = (int) $groupStr[2];

        if ($h === 0 && $t === 0 && $u === 0) {
            return '';
        }

        $words = [];

        // Hundreds
        if ($h > 0 || $hasHigherGroups) {
            $words[] = self::$digits[$h].' trăm';
        }

        // Tens
        if ($t === 0 && $u > 0 && ($h > 0 || $hasHigherGroups)) {
            $words[] = 'lẻ';
        } elseif ($t === 1) {
            $words[] = 'mười';
        } elseif ($t > 1) {
            $words[] = self::$digits[$t].' mươi';
        }

        // Units
        if ($u === 1) {
            if ($t > 1) {
                $words[] = 'mốt';
            } else {
                $words[] = 'một';
            }
        } elseif ($u === 5) {
            if ($t > 0) {
                $words[] = 'lăm';
            } else {
                $words[] = 'năm';
            }
        } elseif ($u > 0) {
            $words[] = self::$digits[$u];
        }

        return implode(' ', $words);
    }
}

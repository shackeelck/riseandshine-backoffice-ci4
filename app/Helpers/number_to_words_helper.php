<?php

if (!function_exists('number_to_words')) {
    /**
     * Convert an integer number into words.
     */
    function number_to_words($number): string
    {
        if (!is_numeric($number)) {
            return '';
        }

        $number = (int) $number;

        if ($number === 0) {
            return 'Zero';
        }

        if ($number < 0) {
            return 'Minus ' . number_to_words(abs($number));
        }

        $ones = [
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
        ];

        $tens = [
            2 => 'Twenty',
            3 => 'Thirty',
            4 => 'Forty',
            5 => 'Fifty',
            6 => 'Sixty',
            7 => 'Seventy',
            8 => 'Eighty',
            9 => 'Ninety',
        ];

        $scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion', 'Quadrillion'];

        $parts = [];
        $scaleIndex = 0;

        while ($number > 0) {
            $chunk = $number % 1000;

            if ($chunk > 0) {
                $chunkWords = _number_to_words_under_thousand($chunk, $ones, $tens);
                $scaleWord = $scales[$scaleIndex] ?? '';
                $parts[] = trim($chunkWords . ' ' . $scaleWord);
            }

            $number = intdiv($number, 1000);
            $scaleIndex++;
        }

        return implode(' ', array_reverse($parts));
    }
}

if (!function_exists('amount_to_words')) {
    /**
     * Convert a decimal amount into invoice-friendly words.
     * Example: 123.45 => "One Hundred Twenty Three Dollars and Forty Five Cents Only"
     */
    function amount_to_words($amount, string $currencyCode = 'USD'): string
    {
        $normalized = str_replace([',', ' '], '', (string) $amount);
        if (!is_numeric($normalized)) {
            return '';
        }

        $value = (float) $normalized;
        $isNegative = $value < 0;
        $value = abs($value);

        $rounded = round($value, 2);
        $whole = (int) floor($rounded);
        $fraction = (int) round(($rounded - $whole) * 100);

        if ($fraction === 100) {
            $whole++;
            $fraction = 0;
        }

        $units = _currency_word_units($currencyCode);
        $majorWord = ($whole === 1) ? $units['major_singular'] : $units['major_plural'];
        $minorWord = ($fraction === 1) ? $units['minor_singular'] : $units['minor_plural'];

        $result = trim(number_to_words($whole) . ' ' . $majorWord);

        if ($fraction > 0) {
            $result .= ' and ' . trim(number_to_words($fraction) . ' ' . $minorWord);
        }

        $result .= ' Only';

        if ($isNegative) {
            $result = 'Minus ' . $result;
        }

        return $result;
    }
}

if (!function_exists('_number_to_words_under_thousand')) {
    /**
     * Convert numbers from 1 to 999 into words.
     *
     * @param array<int, string> $ones
     * @param array<int, string> $tens
     */
    function _number_to_words_under_thousand(int $number, array $ones, array $tens): string
    {
        $words = [];

        if ($number >= 100) {
            $hundreds = intdiv($number, 100);
            $words[] = $ones[$hundreds] . ' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $tenDigit = intdiv($number, 10);
            $words[] = $tens[$tenDigit];
            $number %= 10;
        }

        if ($number > 0 && $number < 20) {
            $words[] = $ones[$number];
        }

        return implode(' ', $words);
    }
}

if (!function_exists('_currency_word_units')) {
    /**
     * Resolve currency unit names for amount_to_words().
     *
     * @return array{major_singular:string,major_plural:string,minor_singular:string,minor_plural:string}
     */
    function _currency_word_units(string $currencyCode): array
    {
        $map = [
            'USD' => ['major_singular' => 'Dollar', 'major_plural' => 'Dollars', 'minor_singular' => 'Cent', 'minor_plural' => 'Cents'],
            'EUR' => ['major_singular' => 'Euro', 'major_plural' => 'Euros', 'minor_singular' => 'Cent', 'minor_plural' => 'Cents'],
            'GBP' => ['major_singular' => 'Pound', 'major_plural' => 'Pounds', 'minor_singular' => 'Penny', 'minor_plural' => 'Pence'],
            'INR' => ['major_singular' => 'Rupee', 'major_plural' => 'Rupees', 'minor_singular' => 'Paisa', 'minor_plural' => 'Paise'],
            'MVR' => ['major_singular' => 'Rufiyaa', 'major_plural' => 'Rufiyaa', 'minor_singular' => 'Laari', 'minor_plural' => 'Laari'],
        ];

        $code = strtoupper(trim($currencyCode));

        return $map[$code] ?? [
            'major_singular' => 'Unit',
            'major_plural' => 'Units',
            'minor_singular' => 'Cent',
            'minor_plural' => 'Cents',
        ];
    }
}

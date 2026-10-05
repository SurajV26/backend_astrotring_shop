<?php

namespace App\Helpers;

class NumberHelper
{
    protected static $ones = [
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

    protected static $tens = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    /**
     * Convert an INR amount to words using the Indian numbering system.
     *
     * Examples:
     * 764.15  => Seven Hundred Sixty-Four Rupees and Fifteen Paise Only
     * 899.00  => Eight Hundred Ninety-Nine Rupees Only
     * 1250.50 => One Thousand Two Hundred Fifty Rupees and Fifty Paise Only
     * 100000  => One Lakh Rupees Only
     * 10000000 => One Crore Rupees Only
     */
    public static function convertToWords($amount)
    {
        $amount = round((float) $amount, 2);

        // Keep the helper safe for negative/invalid invoice values.
        $amount = max(0, $amount);

        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        // Protect against floating-point rounding such as 99.999 -> 100.00.
        if ($paise >= 100) {
            $rupees++;
            $paise = 0;
        }

        $words = self::numberToWords($rupees) . ' Rupees';

        if ($paise > 0) {
            $words .= ' and ' . self::numberToWords($paise) . ' Paise';
        }

        return trim($words) . ' Only';
    }

    protected static function numberToWords($number)
    {
        $number = (int) $number;

        if ($number === 0) {
            return 'Zero';
        }

        $result = '';

        // Crore
        if ($number >= 10000000) {
            $result .= self::numberToWords(intdiv($number, 10000000)) . ' Crore ';
            $number %= 10000000;
        }

        // Lakh
        if ($number >= 100000) {
            $result .= self::numberToWords(intdiv($number, 100000)) . ' Lakh ';
            $number %= 100000;
        }

        // Thousand
        if ($number >= 1000) {
            $result .= self::numberToWords(intdiv($number, 1000)) . ' Thousand ';
            $number %= 1000;
        }

        // Hundred
        if ($number >= 100) {
            $result .= self::numberToWords(intdiv($number, 100)) . ' Hundred ';
            $number %= 100;
        }

        // Remaining 1-99
        if ($number > 0) {
            if ($number < 20) {
                $result .= self::$ones[$number];
            } else {
                $result .= self::$tens[intdiv($number, 10)];

                if (($number % 10) > 0) {
                    $result .= ' ' . self::$ones[$number % 10];
                }
            }
        }

        return trim($result);
    }
}

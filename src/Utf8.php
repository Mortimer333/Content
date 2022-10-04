<?php declare(strict_types=1);

namespace Content;

/**
 *  Class containing text in form of sliced string into UTF-8 letters for easier navigation and memory managment
 */
class Utf8 extends Base
{
    /**
     * https://stackoverflow.com/a/14366023/11495586
     * Retrieve whole UTF-8 letter
     * @param  string $string
     * @param  int    $pointer
     * @param  int    $nextLetter
     * @return string
     */
    public function get(string $string, int $pointer, int &$nextLetter): string|bool
    {
        if (!isset($string[$pointer])) {
            return false;
        }

        $char = ord($string[$pointer]);

        if ($char < 128) {
            $nextLetter = $pointer + 1;
            return $string[$pointer];
        }

        if ($char < 224) {
            $bytes = 2;
        } elseif ($char < 240) {
            $bytes = 3;
        } else {
            $bytes = 4;
        }
        $str = substr($string, $pointer, $bytes);
        $nextLetter = $pointer + $bytes;
        return $str;
    }
}

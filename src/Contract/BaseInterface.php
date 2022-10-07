<?php declare(strict_types=1);

namespace Content\Contract;

interface BaseInterface
{
    public static function get(string $string, int $pointer, int &$nextLetter): string|bool;
}

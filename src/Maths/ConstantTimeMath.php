<?php

namespace Activeledger\Maths;

use GMP;

class ConstantTimeMath extends GmpMath
{
   public function cmp(
        #[\SensitiveParameter]
        GMP $first,
        #[\SensitiveParameter]
        GMP $other
    ): int {
        /**
         * @var string $left
         * @var string $right
         * @var int $length
         */
        list($left, $right, $length) = $this->normalizeLengths($first, $other);

        $first_sign = gmp_sign($first);
        $other_sign = gmp_sign($other);
        list($gt, $eq) = $this->compareSigns($first_sign, $other_sign);

        for ($i = 0; $i < $length; ++$i) {
            $gt |= (($this->ord($right[$i]) - $this->ord($left[$i])) >> 8) & $eq;
            $eq &= (($this->ord($right[$i]) ^ $this->ord($left[$i])) - 1) >> 8;
        }
        return ($gt + $gt + $eq) - 1;
  }


  public function normalizeLengths(
        #[\SensitiveParameter]
        GMP $a,
        #[\SensitiveParameter]
        GMP $b
    ): array {
        $a_hex = gmp_strval(gmp_abs($a), 16);
        $b_hex = gmp_strval(gmp_abs($b), 16);
        $length = max($this->binaryStringLen($a_hex), $this->binaryStringLen($b_hex));
        $length += $length & 1;

        $left = hex2bin(str_pad($a_hex, $length, '0', STR_PAD_LEFT));
        $right = hex2bin(str_pad($b_hex, $length, '0', STR_PAD_LEFT));
        $length >>= 1;
        return [$left, $right, $length];
    }

public function ord(
        #[\SensitiveParameter]
        string $chr
    ): int {
        return (int) unpack('C', $chr)[1];
    }

   public function compareSigns(
        #[\SensitiveParameter]
        int $first_sign,
        #[\SensitiveParameter]
        int $other_sign
    ): array {
        // Coerce to positive (-1, 0, 1) -> (0, 1, 2)
        ++$first_sign;
        ++$other_sign;
        $gt = (($other_sign - $first_sign) >> 2) & 1;
        $eq = ((($first_sign ^ $other_sign) - 1) >> 2) & 1;
        return [$gt, $eq];
    }
}

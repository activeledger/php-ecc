<?php

namespace Activeledger\Maths;

use GMP;

class GmpMath {

  public function getModularArithmetic(GMP $modulus): ModularArithmetic
  {
    return new ModularArithmetic($this, $modulus);
  }

  public function stringToInt(string $s): GMP {
    $result = gmp_init(0, 10);
    $sLen = $this->binaryStringLen($s);

    for ($c = 0; $c < $sLen; $c++) {
      $result = gmp_add(
        gmp_mul(256, $result),
        gmp_init(ord($s[$c]), 10)
      );
    }

    return $result;
  }

  public function inverseMod(GMP $a, GMP $m): GMP
  {
    return gmp_invert($a, $m);
  }

  public function decHex(string $decString): string
  {
    $decString = gmp_init($decString, 10);

    if (gmp_cmp($decString, 0) < 0) {
        throw new \InvalidArgumentException('Unable to convert negative integer to string');
    }

    $hex = gmp_strval($decString, 16);

    if ($this->binaryStringLen($hex) % 2 != 0) {
        $hex = '0'.$hex;
    }

    return $hex;
  }

  public function bitwiseAnd(GMP $first, GMP $other): GMP
  {
    return gmp_and($first, $other);
  }

  public function bitwiseXor(GMP $first, GMP $other): GMP
  {
    return gmp_xor($first, $other);
  }

  public function equals(GMP $a, GMP $b): bool {
    return gmp_cmp($a, $b) === 0;
  }

  public function cmp(GMP $first, GMP $other): int
  {
    return gmp_cmp($first, $other);
  }

  public function sub(GMP $minuend, GMP $subtrahend): GMP
  {
    return gmp_sub($minuend, $subtrahend);
  }

  public function mul(GMP $multiplier, GMP $multiplicand): GMP
  {
    return gmp_mul($multiplier, $multiplicand);
  }

  public function add(GMP $a, GMP $b): GMP
  {
    return gmp_add($a, $b);
  }

  public function pow(GMP $base, int $exponent): GMP
  {
    return gmp_pow($base, $exponent);
  }

  public function rightShift(GMP $number, int $positions): GMP {
    $two = gmp_init(2, 10);
    $exp = gmp_pow($two, $positions);
    $out = gmp_div($number, $exp);
    return $out;
  }

  public function binaryStringLen(string $s): int {
    static $exists = null; 
    if ($exists === null) {
      $exists = function_exists('mb_strlen');
    }

    if ($exists) {
      return mb_strlen($s, '8bit');
    }

    return strlen($s);
  }

 public function mod(GMP $number, GMP $modulus): GMP
    {
        /** @var GMP $out */
        $out = gmp_mod($number, $modulus);
        return $out;
    }

  public function toString(GMP $value): string
    {
        return gmp_strval($value);
  }

  public function baseConvert(string $value, int $fromBase, int $toBase): string
    {
        return gmp_strval(gmp_init($value, $fromBase), $toBase);
    }
}

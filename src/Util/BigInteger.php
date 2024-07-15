<?php

namespace Activeledger\Util;

use RuntimeException;
use InvalidArgumentException;
use OverflowException;

abstract class BigInteger
{
  public static function create(int $val): BigInteger
  {
    if (function_exists('gmp_add')) {
      $returnValue = new BigIntegerGmp();
    } elseif (function_exists('bcadd')) {
      $returnValue = new BigIntegerBc();
    } else {
      throw new RuntimeException('Requires GMP or BCMath extension');
    }

    if (is_int($val)) {
      $returnValue->_fromInteger($val);
    } else {
      $val = (string) $val;

      if (!preg_match('/^-?[0-9]+$/', $val)) {
        throw new InvalidArgumentException('Expected a string representation of an integer');
      }

      $returnValue->_fromString($val);
    }

    return $returnValue;
  }

  abstract protected function _fromInteger(int $val): void;

  abstract protected function _fromString(string $val): void;

  abstract public function add(int $b): BigInteger;

  abstract public function modulus(int $b): BigInteger;

  abstract public function toInteger(): int;

  abstract public function shiftRight(int $shift): BigInteger;

  abstract public function shiftLeft(int $shift): BigInteger;

  abstract public function compare(int $b): int;

  abstract public function absoluteValue(): BigInteger;
}

class BigIntegerGmp extends BigInteger
{
  protected $_rh;

  protected function _fromString(string $val): void
  {
    $this->_rh = gmp_init($val, 10);
  }

  protected function _fromInteger(int $val): void
  {
    $this->_rh = gmp_init($val, 10);
  }

  protected function _unwrap(int $val): int
  {
    if ($val instanceof self) {
      return $val->_rh;
    }

    return $val;
  }

  public function toInteger(): int
  {
    if ($this->compare(PHP_INT_MAX) > 0 || $this->compare(PHP_INT_MIN) < 0) {
      throw new OverflowException(sprintf('Cannot represent %s as an integer', $this));
    }

    return gmp_intval($this->_rh);
  }

  public function __toString(): string
  {
    return gmp_strval($this->_rh);
  }

  public function add(int $b): BigInteger
  {
    $result = new self();
    $result->_rh = gmp_add($this->_rh, $this->_unwrap($b));
    return $result;
  }

  public function modulus(int $b): BigInteger
  {
    $result = new self();
    $result->_rh = gmp_mod($this->_rh, $this->_unwrap($b));
    return $result;
  }

  public function shiftRight(int $bits = 8): BigInteger
  {
    $result = new self();
    $result->_rh = gmp_div($this->_rh, gmp_pow(2, $bits));
    return $result;
  }

  public function shiftLeft(int $bits = 8): BigInteger
  {
    $result = new self();
    $result->_rh = gmp_mul($this->_rh, gmp_pow(2, $bits));
    return $result;
  }

  public function compare(int $b): int
  {
    return gmp_cmp($this->_rh, $this->_unwrap($b));
  }

  public function absoluteValue(): BigInteger
  {
    $result = new self();
    $result->_rh = gmp_abs($this->_rh);
    return $result;
  }
}

class BigIntegerBc extends BigInteger
{

  protected $_str;

  protected function _fromString(string $val): void
  {
    $this->_str = (string) $val;
  }

  protected function _fromInteger(int $val): void
  {
    $this->_str = (string) $val;
  }

  protected function _unwrap(int $val): int
  {
    if ($val instanceof self) {
      return $val->_str;
    }

    return $val;
  }

  public function toInteger(): int
  {
    if ($this->compare(PHP_INT_MAX) > 0 || $this->compare(PHP_INT_MIN) < 0) {
      throw new OverflowException(sprintf('Cannot represent %s as an integer', $this));
    }

    return (int) $this->_str;
  }

  public function __toString(): string
  {
    return $this->_str;
  }

  public function add(int $b): BigInteger
  {
    $returnValue = new self();
    $returnValue->_str = bcadd($this->_str, $this->_unwrap($b), 0);
    return $returnValue;
  }

  public function modulus(int $b): BigInteger
  {
    $returnValue = new self();

    if ($this->isNegative()) {
      $b = $this->_unwrap($b);
      $returnValue->_str = bcsub(
        $b,
        bcmod(
          bcsub('0', $this->_str, 0),
          $b
        ),
        0
      );
    } else {
      $returnValue->_str = bcmod($this->_str, $this->_unwrap($b));
    }

    return $returnValue;
  }

  public function isNegative(): bool
  {
    return bccomp($this->_str, '0', 0) < 0;
  }

  public function shiftRight(int $bits = 8): BigInteger
  {
    $returnValue = new self();
    $returnValue->_str = bcdiv($this->_str, bcpow('2', $bits));

    return $returnValue;
  }

  public function shiftLeft(int $bits = 8): BigInteger
  {
    $returnValue = new self();
    $returnValue->_str = bcmul($this->_str, bcpow('2', $bits), 0);

    return $returnValue;
  }

  public function compare(int $b): int
  {
    return bccomp($this->_str, $this->_unwrap($b), 0);
  }

  public function absoluteValue(): BigInteger
  {
    $returnValue = new self();

    if (bccomp($this->_str, '0', 0) === -1) {
      $returnValue->_str = bcsub('0', $this->_str, 0);
    } else {
      $returnValue->_str = $this->_str;
    }

    return $returnValue;
  }
}


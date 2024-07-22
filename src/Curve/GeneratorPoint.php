<?php

namespace Activeledger\Curve;

use GMP;

use Activeledger\Maths\GmpMath;
use Activeledger\Key\PrivateKey;

class GeneratorPoint extends Point
{
  private $order;
  private $adapter;
  private $curve;

  public function __construct(
    CurveFp $curve,
    GMP $x,
    GMP $y,
    GMP $order
  ) {
    $this->curve = $curve;
    $this->order = $order;
    $this->adapter = new GmpMath();

    parent::__construct($this->adapter, $curve, $x, $y, $order);
  }

  public function getOrder(): GMP {
    return $this->order;
  }

  public function getCurve(): CurveFp {
    return $this->curve;
  }

  public function createPrivateKey(): PrivateKey {
    $secret = $this->generateRandomNumber();
    return new PrivateKey($this->adapter, $this, $secret);
  }

  public function generateRandomNumber(): GMP {
    $numBits = $this->bnNumBits($this->order);
    $numBytes = (int) ceil($numBits / 8);
    $bytes = random_bytes($numBytes);
    
    $value = $this->adapter->stringToInt($bytes);

    $mask = gmp_sub(gmp_pow(2, $numBits), 1);
    $integer = gmp_and($value, $mask);

    return $integer;
  }

  private function bnNumBits(
    #[\SensitiveParameter]
    GMP $x
  ): int {
    /** @var GMP $zero */
    $zero = gmp_init(0, 10);
    if ($this->adapter->equals($x, $zero)) {
        return 0;
    }

    $log2 = 0;
    while (false === $this->adapter->equals($x, $zero)) {
        $x = $this->adapter->rightShift($x, 1);
        $log2++;
    }

    return $log2 ;
  }
}

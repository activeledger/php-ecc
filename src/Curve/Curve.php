<?php

namespace Activeledger\Curve;

use GMP;

use Activeledger\Maths\GmpMath;

class Curve 
{
  private $adapter;

  public function __construct(GmpMath $adapter) {
    $this->adapter = $adapter;
  }

  public function getGenerator(): GeneratorPoint {
    $curve = $this->getCurve();

    /** @var GMP $order */
    $order = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
    /** @var GMP $x */
    $x = gmp_init('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 16);
    /** @var GMP $y */
    $y = gmp_init('0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8', 16);

    return $curve->getGenerator($x, $y, $order);
    
  }

  private function getCurve(): CurveFp {
    $p = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
    $a = gmp_init(0, 10);
    $b = gmp_init(7, 10);

    $parameters = new CurveParameters($p, $a, $b);

    $fp = new CurveFp($parameters, $this->adapter);

    return $fp;
  }
}


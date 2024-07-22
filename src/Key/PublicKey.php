<?php

namespace Activeledger\Key;

use Activeledger\Curve\GeneratorPoint;
use Activeledger\Curve\Point;
use Activeledger\Curve\CurveFp;

use Activeledger\Maths\GmpMath;

use Activeledger\Serialiser\CompressedPointSerialiser;

class PublicKey
{
  private $curve;
  private $adapter;
  private $point;
  private $generator;

  public function __construct(GmpMath $adapter, GeneratorPoint $generator, Point $point) {
    $this->curve = $generator->getCurve();
    $this->point = $point;
    $this->generator = $generator;
    $this->adapter = $adapter;

    if ($point->isInfinity()) {
      throw new \RuntimeException('Public key point is at infinity');
    }

    $zero = gmp_init(0, 10);

    $xNeg = $adapter->cmp($point->getX(), $zero) < 0;
    $yNeg = $adapter->cmp($point->getY(), $zero) < 0;
    $primeXNeg = $adapter->cmp($this->curve->getPrime(), $point->getX()) < 0;
    $primeYNeg = $adapter->cmp($this->curve->getPrime(), $point->getY()) < 0;

    // Public key validation
    if ($xNeg || $yNeg || $primeXNeg || $primeYNeg) {
      throw new \RuntimeException('X and Y of point out of range');
    }

    // Check point values are correct
    if (!$generator->getCurve()->equals($point->getCurve())) {
      throw new \RuntimeException('Given point is not on the same curve as the generator point');
    }
  }

  public function getPoint(): Point {
    return $this->point;
  }

  public function getGenerator(): GeneratorPoint {
    return $this->generator;
  }

  public function getAdapter(): GmpMath {
    return $this->adapter;
  }

  public function getCurve(): CurveFp {
    return $this->curve;
  }

  public function toString(): string {
    $serialiser = new CompressedPointSerialiser($this->generator->getAdapter());
    return $serialiser->serialise($this->point);
  }
}

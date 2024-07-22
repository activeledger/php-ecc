<?php

namespace Activeledger\Curve;

use GMP;

use Activeledger\Maths\GmpMath;
use Activeledger\Maths\ModularArithmetic;

class CurveFp
{
  private $parameters;
  private $adapter = null;
  private $modAdapter = null;

  public function __construct(CurveParameters $parameters, GmpMath $adapter)
  {
    $this->parameters = $parameters;
    $this->adapter = $adapter;

    $this->modAdapter = new ModularArithmetic($adapter, $parameters->getPrime());
  }

  public function getPoint(GMP $x, GMP $y): Point
  {
    return new Point($this->adapter, $this, $x, $y, $order = null);
  }

  public function getModAdapter(): ModularArithmetic
  {
    return $this->modAdapter;
  }

  public function getGenerator(GMP $x, GMP $y, GMP $order): GeneratorPoint
  {
    return new GeneratorPoint($this, $x, $y, $order);
  }

  public function getSize(): int
  {
    return $this->parameters->getSize();
  }

  public function contains(GMP $x, GMP $y): bool
  {
    $math = $this->adapter;
        /** @var GMP $zero */
        $zero = gmp_init(0, 10);

        return $math->equals(
            $this->modAdapter->sub(
                $math->pow($y, 2),
                $math->add(
                    $math->add(
                        $math->pow($x, 3),
                        $math->mul($this->getA(), $x)
                    ),
                    $this->getB()
                )
            ),
            $zero
        );
  }

  public function getPrime(): GMP
  {
    return $this->parameters->getPrime();
  }

  public function equals(CurveFp $other): bool
  {
    return $this->cmp($other) == 0;
  }

  public function cmp(CurveFp $other): int
  {
    $math = $this->adapter;

    $equal  = $math->equals($this->getA(), $other->getA());
    $equal &= $math->equals($this->getB(), $other->getB());
    $equal &= $math->equals($this->getPrime(), $other->getPrime());

    return ($equal) ? 0 : 1;
  }

  public function getInfinity(): Point
  {
    /** @var GMP $zero */
    $zero = gmp_init(0, 10);
    return new Point($this->adapter, $this, clone $zero, clone $zero, null, true);
  }

  public  function getA(): GMP {
    return $this->parameters->getA();
  }

  public function getB(): GMP {
    return $this->parameters->getB();
  }

}

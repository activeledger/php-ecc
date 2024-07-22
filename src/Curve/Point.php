<?php

namespace Activeledger\Curve;

use GMP;

use Activeledger\Maths\GmpMath;
use Activeledger\Maths\ConstantTimeMath;

class Point 
{
  private $curve;
  private $x;
  private $y;
  private $order;
  private $adapter;
  private $infinity = false;
  private $modAdapter;
  private $ctMath;

  public function __construct(
    GmpMath $adapter,
    CurveFp $curve,
    GMP $x,
    GMP $y,
    ?GMP $order = null,
    bool $infinity = false
  ) {
    $zero = gmp_init(0, 10);
    $this->adapter = $adapter;
    $this->modAdapter = $curve->getModAdapter();
    $this->ctMath = new ConstantTimeMath();

    $this->curve = $curve;
    $this->x = $x;
    $this->y = $y;
    $this->order = $order !== null ? $order : $zero;
    $this->infinity = !empty($infinity);

    if (!$infinity && !$curve->contains($x, $y)) {
      throw new \RuntimeException('Point not on curve');
    }

    if (!is_null($order)) {
      $mul = $this->mul($order);
      if (!$mul->isInfinity()) {
        throw new \RuntimeException('Self multiplied by order must equal infinity');
      }
    }
  }

  public function getAdapter(): GmpMath {
    return $this->adapter;
  }

  public function isInfinity(): bool {
    return (bool) $this->infinity;
  }

  public function add(Point $addend): Point
    {
        if (! $this->curve->equals($addend->getCurve())) {
            throw new \RuntimeException("The Elliptic Curves do not match.");
        }

        if ($addend->isInfinity()) {
            return clone $this;
        }

        if ($this->isInfinity()) {
            return clone $addend;
        }
        /** @var Point $infinity */
        $infinity = $this->curve->getInfinity();

        $math = $this->ctMath;
        $modMath = $this->modAdapter;

        // if (x1 == x2)
        $return = $this->getDouble();
        if ($math->equals($addend->getX(), $this->x)) {
            // if (y1 == y2) return doubled(), else return pointAtInfinity()
            // Avoids leaking comparison value via branching side-channels
            $bit = $math->equalsReturnInt($addend->getY(), $this->y);
            $this->cswap($return, $infinity, $bit ^ 1, $this->curve->getSize());
            return $return;
        }

        $slope = $modMath->div(
            $math->sub($addend->getY(), $this->y),
            $math->sub($addend->getX(), $this->x)
        );

        $xR = $modMath->sub(
            $math->sub($math->pow($slope, 2), $this->x),
            $addend->getX()
        );

        $yR = $modMath->sub(
            $math->mul($slope, $math->sub($this->x, $xR)),
            $this->y
        );

        return $this->curve->getPoint($xR, $yR, $this->order);
    }

  public function getDouble(): Point
  {
    if ($this->isInfinity()) {
      return $this->curve->getInfinity();
    } 

    $math = new ConstantTimeMath();
    $modMath = $this->modAdapter;

    /** @var GMP $two */
    $two = gmp_init(2, 10);
    /** @var GMP $three */
    $three = gmp_init(3, 10);

    $a = $this->curve->getA();
    $threeX2 = $math->mul($three, $math->pow($this->x, 2));

    $tangent = $modMath->div(
        $math->add($threeX2, $a),
        $math->mul($two, $this->y)
    );

    $x3 = $modMath->sub(
        $math->pow($tangent, 2),
        $math->mul($two, $this->x)
    );

    $y3 = $modMath->sub(
        $math->mul($tangent, $math->sub($this->x, $x3)),
        $this->y
    );

    return $this->curve->getPoint($x3, $y3, $this->order);
  }

  public function mul(GMP $multiplier): Point {
    if ($this->isInfinity()) {
      return $this->curve->getInfinity();
    }
    $constantTimeAdapter = new ConstantTimeMath();

    /** @var GMP $zero */
    $zero = gmp_init(0, 10);
    if ($constantTimeAdapter->cmp($this->order, $zero) > 0) {
      $multiplier = $constantTimeAdapter->mod($multiplier, $this->order);
    }

    if ($constantTimeAdapter->equals($multiplier, $zero)) {
      return $this->curve->getInfinity();
    }

    /** @var Point[] $r */
    $r = [
      $this->curve->getInfinity(),
      clone $this
    ];

    $k = $this->curve->getSize();
    $multiplier = str_pad(
      $constantTimeAdapter->baseConvert(
        $constantTimeAdapter->toString($multiplier),
        10,
        2
      ),
      $k,
      '0',
      STR_PAD_LEFT
    );

    for ($i = 0; $i < $k; $i++) {
      $j = $multiplier[$i];

      $this->cswap($r[0], $r[1], $j ^ 1, $k);

      $r[0] = $r[0]->add($r[1]);
      $r[1] = $r[1]->getDouble();

      $this->cswap($r[0], $r[1], $j ^ 1, $k);
    }

    $r[0]->validate();

    return $r[0];
  }

  public function getX(): GMP {
    return $this->x;
  }

  public function getY(): GMP {
    return $this->y;
  }

  public function getCurve(): CurveFp {
    return $this->curve;
  }

  protected function cswap(self $a, self $b, int $cond, int $curveSize): void
    {
        $this->cswapValue($a->x, $b->x, $cond, $curveSize);
        $this->cswapValue($a->y, $b->y, $cond, $curveSize);
        $this->cswapValue($a->order, $b->order, $cond, $curveSize);
        $this->cswapValue($a->infinity, $b->infinity, $cond, 8);
    }

  protected function validate(): void
  {
    if (!$this->infinity && !$this->curve->contains($this->x, $this->y)) {
      throw new \RuntimeException('Point not on curve');
    }
  }

  /**
   * @param GMP|bool $a
   * @param GMP|bool $b
   * @param int $cond
   * @param int $maskBitSize
   */
  private function cswapValue(& $a, & $b, int $cond, int $maskBitSize): void
  {
    $isGMP = is_object($a) && $a instanceof GMP;

    $sa = $isGMP ? $a : gmp_init(intval($a), 10);
    $sb = $isGMP ? $b : gmp_init(intval($b), 10);

    /** @var GMP $mask */
    $mask = str_pad('', $maskBitSize, (string) (1 - intval($cond)), STR_PAD_LEFT);
    $mask = gmp_init($mask, 2);

    $taA = $this->adapter->bitwiseAnd($sa, $mask);
    $taB = $this->adapter->bitwiseAnd($sb, $mask);

    /** @var GMP $sa */
    $sa = $this->adapter->bitwiseXor($this->adapter->bitwiseXor($sa, $sb), $taB);
    /** @var GMP $sb */
    $sb = $this->adapter->bitwiseXor($this->adapter->bitwiseXor($sa, $sb), $taA);
    /** @var GMP $sa */
    $sa = $this->adapter->bitwiseXor($this->adapter->bitwiseXor($sa, $sb), $taB);

    $a = $isGMP ? $sa : (bool) gmp_strval($sa, 10);
    $b = $isGMP ? $sb : (bool) gmp_strval($sb, 10);
  }
}

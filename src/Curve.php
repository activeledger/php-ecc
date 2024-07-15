<?php

namespace Activeledger;

use GMP;

use Activeledger\ASN1\ObjectIdentifier;

class Curve 
{
  private $adapter;

  public function __construct() {
    $this->adapter = new GmpMath();
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

    $parameters = new CurveParameters(256, $p, $a, $b);

    $fp = new CurveFp($parameters, $this->adapter);

    return $fp;
  }
}

class CurveOIDData
{
  const BYTE_SIZE = 32;
  const OID = '1.3.132.0.10';

  public static function getByteSize(): int
  {
    return self::BYTE_SIZE;
  }

  public static function getOID(): ObjectIdentifier
  {
    return new ObjectIdentifier(self::OID);
  }
}


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

class CurveParameters 
{
  private $a;
  private $b;
  private $prime;
  private $size;

  public function __construct(int $size, GMP $prime, GMP $a, GMP $b) {
    $this->size = $size;
    $this->prime = $prime;
    $this->a = $a;
    $this->b = $b;
  }

  public function getA(): GMP {
    return $this->a;
  }

  public function getB(): GMP {
    return $this->b;
  }

  public function getPrime(): GMP {
    return $this->prime;
  }

  public function getSize(): int {
    return $this->size;
  }
}


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

  /* public function createPrivateKey(): PrivateKey {
    $secret = $this->generateRandomNumber();
    return new PrivateKey($this, $secret);
  } */

  public function getOrder(): GMP {
    return $this->order;
  }

  public function getCurve(): CurveFp {
    return $this->curve;
  }

  public function mul(GMP $multiplier): Point {
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

    $this->x;

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

    $zero = gmp_init(0, 10);
    if ($constantTimeAdapter->cmp($this->order, $zero) > 0) {
      $multiplier = $constantTimeAdapter->mod($multiplier, $this->order);
    }

    if ($constantTimeAdapter->equals($multiplier, $zero)) {
      return $this->curve->getInfinity();
    }

    $r = [
      $this->curve->getInfinity(),
      clone $this
    ];

    $k = $this->curve->getSize();
    $mutliplier = str_pad(
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

  private function cswapValue(& $a, & $b, int $cond, int $maskBitSize): void
    {
        $isGMP = is_object($a) && $a instanceof GMP;

        $sa = $isGMP ? $a : gmp_init(intval($a), 10);
        $sb = $isGMP ? $b : gmp_init(intval($b), 10);

        $mask = str_pad('', $maskBitSize, (string) (1 - intval($cond)), STR_PAD_LEFT);
        $mask = gmp_init($mask, 2);
        /** @var GMP $mask */

    // INFO: in point
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

    if (
      $adapter->cmp($point->getX(), $zero) < 0 ||
        $adapter->cmp($this->curve->getPrime(), $point->getX()) < 0 ||
        $adapter->cmp($point->getY(), $zero) < 0 ||
        $adapter->cmp($this->curve->getPrime(), $point->getY()) < 0
    ) {
      throw new \RuntimeException('X and Y of point out of range');
    }

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
    $serializer = new CompressedPointSerializer($this->generator->getAdapter());
    return $serializer->serialize($this->point);
  }
}



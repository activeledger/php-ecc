<?php

namespace Activeledger\Signature;

use GMP;

use Activeledger\ConstantTimeMath;
use Activeledger\PrivateKey;

class IntSigner 
{
  private $adapter;

  public function __construct()
  {
    $this->adapter = new ConstantTimeMath();
  }

  public function sign(
    #[\SensitiveParameter]
    PrivateKey $key,
    #[\SensitiveParameter]
    GMP $truncatedHash,
    #[\SensitiveParameter]
    GMP $randomK
  ): Signature {
    $math = new ConstantTimeMath();
    $generator = $key->getGenerator();
    $curve = $generator->getCurve();
    $modMath = $math->getModularArithmetic($generator->getOrder());

    $k = $math->mod($randomK, $generator->getOrder());
    $p1 = $generator->mul($k);

    $r = $p1->getX();
    /** @var GMP $zero */
    $zero = gmp_init(0, 10);
    if ($math->equals($r, $zero)) {
        throw new \RuntimeException("Error: random number R = 0");
    }
    $kInv = $math->inverseMod($k, $generator->getOrder());

    // S = (d*R + h) / k (mod P) = (d*R + h) * k^-1 (mod P)
    $s = $modMath->mul(
        $modMath->add($truncatedHash, $math->mul($key->getSecret(), $r)),
        $kInv
    );
    if ($math->equals($s, $zero)) {
        throw new \RuntimeException("Error: random number S = 0");
    }

    // Prevent high-order values for S
    if ($this->disallowMalleableSig) {
        $n = $generator->getOrder();
        $halfOrder = $math->rightShift($n, 1);
        if ($math->cmp($s, $halfOrder) > 0) {
            $s = $math->sub($n, $s);
        }
    }

    return new Signature($r, $s);

  }
}

class Signature
{

  private $r;
  private $s;

  public function __construct(GMP $r, GMP $s)
  {
    $this->r = $r;
    $this->s = $s;
  }

  public function getR(): GMP
  {
    return $this->r;
  }

  public function getS(): GMP
  {
    return $this->s;
  }

}

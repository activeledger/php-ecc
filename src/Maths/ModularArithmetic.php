<?php

namespace Activeledger\Maths;

use GMP;

class ModularArithmetic {

  private $adapter;
  private $modulus;

  public function __construct(GmpMath $adapter, GMP $modulus) {
    $this->adapter = $adapter;
    $this->modulus = $modulus;
  }

  public function add(GMP $augend, GMP $addend): GMP
  {
    return $this->adapter->mod($this->adapter->add($augend, $addend), $this->modulus);
  }

  public function sub(GMP $minuend, GMP $subtrahend): GMP
  {
    return $this->adapter->mod($this->adapter->sub($minuend, $subtrahend), $this->modulus);
  }

  public function div(GMP $dividend, GMP $divisor): GMP
  {
    return $this->mul($dividend, $this->adapter->inverseMod($divisor, $this->modulus));
  }

  public function mul(GMP $multiplier, GMP $multiplicand): GMP
  {
    return $this->adapter->mod($this->adapter->mul($multiplier, $multiplicand), $this->modulus);
  }
}

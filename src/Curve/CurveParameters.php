<?php

namespace Activeledger\Curve;

use GMP;

class CurveParameters 
{
  private $a;
  private $b;
  private $prime;
  private $size;

  public function __construct(GMP $prime, GMP $a, GMP $b) {
    $this->size = 256;
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

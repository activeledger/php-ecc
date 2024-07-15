<?php

namespace Activeledger;
use GMP;

class RandomGenerator
{
  public function __construct()
  {
    $this->adapter = new GmpMath();
    
  }

}



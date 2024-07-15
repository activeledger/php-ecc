<?php

namespace Activeledger;

class CompressedPointSerializer
{
   private $adapter;

  public function __construct(GmpMath $adapter)
  {
    $this->adapter = $adapter;
  }

  public function serialize(Point $point): string
  {
    // SECP256K1 bytesize is 32
    $length = 32 * 2;

    $hexString = $this->getPrefix($point);
    $hexString .= str_pad(
      gmp_strval($point->getX(), 16),
      $length,
      '0',
      STR_PAD_LEFT
    );

    return $hexString;
  }

  private function getPrefix(Point $point): string
  {
    $two = gmp_init(2, 10);
    $zero = gmp_init(0, 10);

    if ($this->adapter->equals(
      $this->adapter->mod($point->getY(), $two), 
      $zero
    )) {
      return '02';
    } else {
      return '03';
    }
  }
}

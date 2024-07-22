<?php

namespace Activeledger\Serialiser;

use Activeledger\Curve\Point;
use Activeledger\Maths\GmpMath;

class CompressedPointSerialiser
{
   private $adapter;

  public function __construct(GmpMath $adapter)
  {
    $this->adapter = $adapter;
  }

  public function serialise(Point $point): string
  {
    // SECP256K1 bytesize is 32
    $length = 32 * 2;

    $xString = gmp_strval($point->getX(), 16);

    $hexString = $this->getPrefix($point);
    $hexString .= str_pad(
      $xString,
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

    $yMod = $this->adapter->mod($point->getY(), $two);
    $modIsZero = $this->adapter->equals($yMod, $zero);

    if ($modIsZero) {
      return '02';
    } else {
      return '03';
    }
  }
}

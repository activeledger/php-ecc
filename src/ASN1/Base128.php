<?php

namespace Activeledger\ASN1;

use InvalidArgumentException;

use Activeledger\Util\BigInteger;

class Base128
{
  public static function encode(int $value): string
  {
    $value = BigInteger::create($value);
    $octets = chr($value->modulus(0x80)->toInteger());

    $value = $value->shiftRight(7);
    while ($value->compare(0) > 0) {
      $octets .= chr(0x80 | $value->modulus(0x80)->toInteger());
      $value = $value->shiftRight(7);
    }

    return strrev($octets);
  }

  public static function decode(string $octets): int
  {
    $bitsPerOctet = 7;
    $value = BigInteger::create(0);
    $i = 0;

    while (true) {
      if (!isset($octets[$i])) {
        throw new InvalidArgumentException(
          sprintf(
            'Malformed base-128 encoded value (0x%s).',
            strtoupper(bin2hex($octets)) ?: '0'
          )
        );
      }

      $octet = ord($octets[$i++]);

      $l1 = $value->shiftLeft($bitsPerOctet);
      $r1 = $octet & 0x7f;
      $value = $l1->add($r1);

      if (0 === ($octet & 0x80)) {
        break;
      }
    }

    return (string)$value;
  }
}

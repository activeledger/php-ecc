<?php

namespace Activeledger\ASN1;

use Exception;

use Activeledger\ASN1\Base128;

class Identifier
{
  const CLASS_UNIVERSAL         = 0x00;
  const CLASS_CONTEXT_SPECIFIC  = 0x02;
  const CLASS_PRIVATE           = 0x03;

  const LONG_FORM         = 0x1F;

  const INTEGER           = 0x02;
  const BIT_STRING        = 0x03;
  const OCTET_STRING      = 0x04;
  const OBJECT_IDENTIFIER = 0x06;

  const SEQUENCE          = 0x30;

  public static function create(int $class, bool $isConstructed, int $tagNumber): int|string
  {
    if (
      !is_numeric($class) ||
      $class < self::CLASS_UNIVERSAL ||
      $class > self::CLASS_PRIVATE
    ) {
      throw new Exception('Invalid class');
    }

    if (!is_bool($isConstructed)) {
      throw new Exception('Constructed must be a boolean');
    }

    $tagNumber = self::makeNumeric($tagNumber);

    if ($tagNumber < 0) {
      throw new Exception('Tag number must be a positive integer');
    }

    if ($tagNumber < self::LONG_FORM) {
      return ($class << 6) | ($isConstructed << 5) | $tagNumber;
    }

    $firstOctet = ($class << 6) | ($isConstructed << 5) | self::LONG_FORM;

    $id = chr($firstOctet).Base128::encode($tagNumber);

    echo "Returning identifier: " . bin2hex($firstOctet) . "\n";
    echo "Encoded tag number: " . bin2hex(Base128::encode($tagNumber)) . "\n";

    return $id;
  }

  public static function isLongForm(int $identifierOctet): bool
  {
    return ($identifierOctet & self::LONG_FORM) === self::LONG_FORM;
  }

  private static function makeNumeric(int $value): int
  {
    // value = identifierOctet
    if (!is_numeric($value)) {
      return ord($value);
    }

    return $value;
  }
}

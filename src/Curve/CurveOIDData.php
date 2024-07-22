<?php

namespace Activeledger\Curve;

use Activeledger\ASN1\ObjectIdentifier;

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

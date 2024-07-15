<?php

namespace Activeledger\ASN1;

use Exception;

class BitString extends OctetString
{
  
  private $numberOfUnusedBits;

  public function __construct(string $value, int $numberOfUnusedBits = 0)
  {
    parent::__construct($value);

    if (!is_numeric($numberOfUnusedBits) || $numberOfUnusedBits < 0) {
      throw new Exception('Number of unused bits must be zero or greater');
    }

    $this->numberOfUnusedBits = $numberOfUnusedBits;
  }
  
  public function getType(): int
  {
    return Identifier::BIT_STRING;
  }

  public function getEncodedValue(): string
  {
    $numberOfUnusedBitsOctet = chr($this->numberOfUnusedBits);
    $actualContent = parent::getEncodedValue();

    return $numberOfUnusedBitsOctet . $actualContent;
  }

  public function calculateContentLength(): int
  {
    return parent::calculateContentLength() + 1;
  }

  public function getNumberOfUnusedBits(): int
  {
    return $this->numberOfUnusedBits;
  }
}

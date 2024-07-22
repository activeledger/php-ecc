<?php

namespace Activeledger\ASN1;

use Exception;

use Activeledger\ASN1\ASNObject;
use Activeledger\ASN1\Base128;


class ObjectIdentifier extends ASNObject
{
  private $value;
  private $subIdentifiers;

  public function __construct(string $value)
  {

    $this->subIdentifiers = explode('.', $value);
    $nrOfSubIdentifiers = count($this->subIdentifiers);

    for ($i = 0; $i < $nrOfSubIdentifiers; $i++) {
      if (is_numeric($this->subIdentifiers[$i])) {
        // enforce the integer type
        $this->subIdentifiers[$i] = intval($this->subIdentifiers[$i]);
      } else {
        throw new Exception("[{$value}] is no valid object identifier (sub identifier ".($i + 1).' is not numeric)!');
      }
    }

    // Merge the first to arcs of the OID registration tree (per ASN definition!)
    if ($nrOfSubIdentifiers >= 2) {
      $this->subIdentifiers[1] = ($this->subIdentifiers[0] * 40) + $this->subIdentifiers[1];
      unset($this->subIdentifiers[0]);
    }

    $this->value = $value;
  }

  public function getContent(): string
  {
    return $this->value;
  }

  public function getType(): int
  {
    return Identifier::OBJECT_IDENTIFIER;
  }

  protected function calculateContentLength(): int
  {
    $length = 0;

    foreach ($this->subIdentifiers as $subIdentifier) {
      do {
        $subIdentifier = $subIdentifier >> 7;
        $length++;
      } while ($subIdentifier > 0);
    }

    return $length;
  }

  public function getEncodedValue(): string
  {
    $encodedValue = '';

    $i = 0;
    foreach($this->subIdentifiers as $subIdentifier) {
      $enc = Base128::encode($subIdentifier);
      $encodedValue .= $enc;

      $i++;
    }

    return $encodedValue;
  }
}


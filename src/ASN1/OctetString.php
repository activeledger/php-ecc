<?php

namespace Activeledger\ASN1;

use Exception;

use Activeledger\ASN1\ASNObject;
use Activeledger\ASN1\Identifier;

class OctetString extends ASNObject
{
  protected $value;

  public function __construct(string $value)
  {
    if (is_string($value)) {
      $value = preg_replace('/\s|0x/', '', $value);
    } elseif (is_numeric($value)) {
      $value = dechex($value);
    } elseif ($value === null) {
      return;
    } else {
      throw new Exception('Unrecognised OctetString input type');
    }

    if (strlen($value) % 2 !== 0) {
      $value = '0' . $value;
    }

    $this->value = $value;
  }

  public function getType(): int
  {
    return Identifier::OCTET_STRING;
  }

  public function getContent(): string
  {
    if (is_null($this->value)) {
      return '';
    }

    return strtoupper($this->value);
  }

  public function getBinaryContent(): string {
    return $this->getEncodedValue();
  }

  protected function getEncodedValue(): string
  {
    $value = $this->value;
    if (is_null($value)) {
      return '';
    }
    // This appears to expect hex strings but sometimes is populated by binary data
    $result = '';

    // Actual content
    while (strlen($value) >= 2) {
      // get the hex value byte by byte from the string and add it to binary result
      $result .= @chr(hexdec(substr($value, 0, 2)));
      $value = substr($value, 2);
    }

    return $result;
  }

  protected function calculateContentLength(): int
  {
    if (is_null($this->value)) {
      return 0;
    }

    return strlen($this->value) / 2;
  }

}



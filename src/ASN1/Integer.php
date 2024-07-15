<?php

namespace Activeledger\ASN1;

use Exception;
use Activeledger\ASN1\ASNObject;
use Activeledger\ASN1\Identifier;
use Activeledger\Util\BigInteger;

class Integer extends ASNObject
{
  private $value;

  public function __construct(int $value)
  {
    if (!is_numeric($value)) {
      throw new Exception('Value must be numeric for ASN1 Integer');
    }

    $this->value = $value;
  }

  public function getType(): int
  {
    return Identifier::INTEGER;
  }

  public function getContent(): int
  {
    return $this->value;
  }

  protected function calculateContentLength(): int
  {
    return strlen($this->getEncodedValue());
  }

  protected function getEncodedValue(): string
  {
    $value = BigInteger::create($this->value, 10);

    $negative = $value->compare(0) < 0;

    if ($negative) {
      $value = $value->absoluteValue();
      $limit = 0x80;
    } else {
      $limit = 0x7f;
    }

    $mod = 0xff+1;
    $values = [];

    while ($value->compare($limit) > 0) {
      $values[] = $value->modulus($mod)->toInteger();
      $value = $value->shiftRight(8);
    }

    $values[] = $value->modulus($mod)->toInteger();
    $numValues = count($values);

    if ($negative) {
      for ($i = 0; $i < $numValues; $i++) {
        $values[$i] = 0xff - $values[$i];
      }

      for ($i = 0; $i < $numValues; $i++) {
        $values[$i] += 1;

        if ($values[$i] <= 0xff) {
          break;
        }

        assert($i != $numValues - 1);
        $values[$i] = 0;
      }

      if ($values[$numValues - 1] == 0x7f) {
        $values[] = 0xff;
      }
    }

    $values = array_reverse($values);
    $r = pack("C*", ...$values);
    return $r;
  }

}

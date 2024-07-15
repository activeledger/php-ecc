<?php

namespace Activeledger\ASN1;

use Activeledger\ASN1\ASNObject;

class Construct extends ASNObject
{
  protected $children;
  private $iteratorPosition;

  public function __construct(mixed ...$children)
  {
    $this->children = $children;
    $this->iteratorPosition = 0;
  }

  public function getType(): int
  {
    parent::getType();
  }

  public function getContent(): array
  {
    return $this->children;
  }

  public function getEncodedValue(): string
  {
    $result = '';

    foreach ($this->children as $child) {
      $result .= $child->getBinary();
    }

    return $result;
  }

  protected function calculateContentLength(): int
  {
    $length = 0;

    foreach ($this->children as $child) {
      $length += $child->getObjectLength();
    }

    return $length;
  }

}

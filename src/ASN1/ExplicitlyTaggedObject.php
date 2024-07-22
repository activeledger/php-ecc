<?php

namespace Activeledger\ASN1;

use Activeledger\ASN1\ASNObject;

class ExplicitlyTaggedObject extends ASNObject
{

  private $decoratedObjects;
  private $tag;

  public function __construct(int $tag, ASNObject ...$decoratedObjects)
  {
    $this->tag = $tag;
    $this->decoratedObjects = $decoratedObjects;
  }

  public function getType(): int
  {
    return ord($this->getIdentifier());
  }

  // @return ASNObject[]
  public function getContent(): array
  {
    return $this->decoratedObjects;
  }

  public function getIdentifier(): string
  {
    $identifier = Identifier::create(Identifier::CLASS_CONTEXT_SPECIFIC, true, $this->tag);

    return is_int($identifier) ? chr($identifier) : $identifier;
  }

  public function getTag(): int
  {
    return $this->tag;
  }

  public function getEncodedValue(): string
  {
    $encoded = '';

    foreach ($this->decoratedObjects as $obj) {
      $encoded .= $obj->getBinary();
    }

    return $encoded;
    
  }

  protected function calculateContentLength(): int
  {
    $length = 0;

    foreach ($this->decoratedObjects as $obj) {
      $length += $obj->getObjectLength();
    }

    return $length;
  }

}

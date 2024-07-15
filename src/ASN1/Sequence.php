<?php

namespace Activeledger\ASN1;

use Activeledger\ASN1\Identifier;
use Activeledger\ASN1\Construct;

class Sequence extends Construct
{
  public function getType(): int
  {
    return Identifier::SEQUENCE;
  }
}


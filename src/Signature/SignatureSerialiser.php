<?php

namespace Activeledger\Signature;

use Exception;

use Activeledger\Signature\Signature;
use Activeledger\ASN1\Identifier;


class SignatureSerialiser
{

  private $formatter;

  public function __construct()
  {
  }

  // public function serialise(Signature $signature): string
  // {
  //   return $this->toAsn($signature)->getBinary();
  // }

  // public function parse(string $data): Signature
  // {
  //   $offsetIndex = 0;
  //   $asnObject = ASNObject::fromBinary($binary, $offsetIndex);
  //
  //   if ($offsetIndex != strlen($binary)) {
  //     throw new Exception('Invalid data.');
  //   }
  //
  //   // Set inherits from Sequence, so use getType!
  //   if ($asnObject->getType() !== Identifier::SEQUENCE) {
  //     throw new Exception('Invalid tag for sequence.');
  //   }
  //
  //   if ($asnObject->getNumberofChildren() !== 2) {
  //     throw new Exception('Invalid data.');
  //   }
  //
  //   if (!($asnObject[0] instanceof Integer && $asnObject[1] instanceof Integer)) {
  //     throw new Exception('Invalid data.');
  //   }
  //
  //   /** @var GMP $r */
  //   $r = gmp_init($asnObject[0]->getContent(), 10);
  //
  //   /** @var GMP $s */
  //   $s = gmp_init($asnObject[1]->getContent(), 10);
  //
  //   return new Signature($r, $s);
  // }
  //
  // private function toAsn(Signature $signature): string
  // {
  //   return new Sequence(
  //     new Integer($signature->getR(), 10),
  //     new Integer($signature->getS(), 10)
  //   );
  // }
}


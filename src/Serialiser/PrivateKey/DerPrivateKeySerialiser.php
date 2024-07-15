<?php

namespace Activeledger\Serialiser\PrivateKey;

use Activeledger\PrivateKey;
use Activeledger\CurveOIDData;
use Activeledger\ASN1\Sequence;
use Activeledger\ASN1\Integer;
use Activeledger\ASN1\OctetString;
use Activeledger\ASN1\ExplicitlyTaggedObject;
use Activeledger\ASN1\BitString;

class DerPrivateKeySerialiser
{
  const VERSION = 1;

  public function serialise(
    #[\SensitiveParameter]
    PrivateKey $key
  ): string {

    $int = new Integer(SELF::VERSION);
    $oct = new OctetString($this->formatKey($key));
    $exp = new ExplicitlyTaggedObject(0, CurveOIDData::getOID());
    $exp2 = new ExplicitlyTaggedObject(1, $this->encodePublicKey($key));

    echo "Int: " . $int . "\n";
    echo "Oct: " . $oct . "\n";

    foreach ($exp->getContent() as $object) {
      echo "Exp object: " . $object . "\n";
    }

    foreach ($exp2->getContent() as $object) {
      echo "Exp 2 object: " . $object . "\n";
    }

    /* $keyInfo = new Sequence(
      new Integer(SELF::VERSION),
      new OctetString($this->formatKey($key)),
      new ExplicitlyTaggedObject(0, CurveOIDData::getOID()),
      new ExplicitlyTaggedObject(1, $this->encodePublicKey($key))
    ); */

    $keyInfo = new Sequence(
      $int,
      $oct,
      $exp,
      $exp2
    );

    return $keyInfo->getBinary();
  }

  private function formatKey(
    #[\SensitiveParameter]
    PrivateKey $key
  ): string {
    return gmp_strval($key->getSecret(), 16);
  }

  private function encodePublicKey(
    #[\SensitiveParameter]
    PrivateKey $key
  ): BitString {

    $publicKey = $key->getPublicKey();
    $point = $publicKey->getGenerator();

    // echo "Public key: " . $publicKey . "\n";
    echo "Point X: " . $point->getX() . "\n";
    echo "Point Y: " . $point->getY() . "\n";

    $length = CurveOIDData::getByteSize() * 2;

    $hexString = '04';
    $hexString .= str_pad(gmp_strval($point->getX(), 16), $length, '0', STR_PAD_LEFT);
    $hexString .= str_pad(gmp_strval($point->getY(), 16), $length, '0', STR_PAD_LEFT);

    return new BitString(
      $hexString
    );
  }
}

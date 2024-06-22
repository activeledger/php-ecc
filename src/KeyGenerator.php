<?php

namespace Activeledger;

use ParagonIE\EasyECC\EasyECC;

class KeyGenerator
{
  private $ecc;

  public function __construct()
  {
    $this->ecc = new EasyECC('K256');
  }

  public function generateKey()
  {
    $privatekey = $this->ecc->generatePrivateKey();
    $publickey = $privatekey->getPublicKey();

    $gmpNum = $privatekey->getSecret();
    $privatekeyHex = '0x' . gmp_strval($gmpNum, 16);

    $publicCompressed = $publickey->toString();
    $publickeyHex = '0x' . $publicCompressed;

    return [
      'private' => $privatekeyHex,
      'public' => $publickeyHex
    ];
  }
}

<?php

namespace Activeledger;

use Activeledger\PrivateKey;

class KeyGenerator
{
  private $config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_EC,
    "curve_name" => "secp256k1"
  );

  /**
  * @return string[]
  */
  public function generateKey(): array
  {
    return $this->gen();
  }

  /**
  * @return string[]
  */
  private function gen(): array
  {
    $privateKey = PrivateKey::generate();
    $privateKeyHex = $this->privateToHex($privateKey);
    $publicKeyHex = $this->getPublicHex($privateKey);

    return [
      'private' => $privateKeyHex,
      'public' => $publicKeyHex
    ];
  }

  private function privateToHex(PrivateKey $privateKey): string
  {
    $privateKeyNum = $privateKey->getSecret();
    $privateKeyHex = '0x' . gmp_strval($privateKeyNum, 16);
    return $privateKeyHex;
  }

  private function getPublicHex(PrivateKey $privateKey): string
  {
    $publicKey = $privateKey->getPublicKey();
    $publicKeyCompressed = $publicKey->toString();
    $publicKeyHex = '0x' . $publicKeyCompressed;

    return $publicKeyHex;
  }
}

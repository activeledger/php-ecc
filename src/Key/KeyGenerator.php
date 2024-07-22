<?php

namespace Activeledger\Key;

class KeyGenerator
{
  /**
  * @return string[]
  */
  public static function generateKey(): array
  {
    $privateKey = PrivateKey::generate();
    $privateKeyHex = self::privateToHex($privateKey);
    $publicKeyHex = self::getPublicHex($privateKey);

    return [
      'private' => $privateKeyHex,
      'public' => $publicKeyHex
    ];
  }

  private static function privateToHex(PrivateKey $privateKey): string
  {
    $privateKeyNum = $privateKey->getSecret();
    $privateKeyHex = '0x' . gmp_strval($privateKeyNum, 16);
    return $privateKeyHex;
  }

  private static function getPublicHex(PrivateKey $privateKey): string
  {
    $publicKey = $privateKey->getPublicKey();
    $publicKeyCompressed = $publicKey->toString();
    $publicKeyHex = '0x' . $publicKeyCompressed;

    return $publicKeyHex;
  }
}

<?php

namespace Activeledger;

class KeyGenerator
{
  private $config = array(
    "digest_alg" => "sha256",
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_EC,
    "curve_name" => "secp256k1"
  );

  public function generateKey()
  {

    $privateKeyResult = openssl_pkey_new($this->config);

    openssl_pkey_export($privateKeyResult, $privateKey);

    $details = openssl_pkey_get_details($privateKeyResult);

    // $privatekeyHex = ;
    // echo "Private: " . $privatekeyHex . "\n";
    //

    $privatekeyHex = $this->privateToHex($details["ec"]["d"]);

    $publickeyHex = $this->publicToHex($details["ec"]["x"], $details["ec"]["y"]);
    // echo "Public: " . $publickeyHex . "\n";

    // $privatekey = $this->ecc->generatePrivateKey();
    // $publickey = $privatekey->getPublicKey();
    //
    // $gmpNum = $privatekey->getSecret();
    // $privatekeyHex = '0x' . gmp_strval($gmpNum, 16);
    //
    // $publicCompressed = $publickey->toString();
    // $publickeyHex = '0x' . $publicCompressed;

    return [
      'private' => $privatekeyHex,
      'public' => $publickeyHex
    ];
  }

  private function privateToHex(string $privateKeyD)
  {
    $key = ltrim($privateKeyD, '0');
    $hex = bin2hex($key);
    return '0x' . $hex;
  }

  private function publicToHex(string $x, string $y)
  {
    $yGmp = gmp_import($y);

    $prefix = (gmp_intval(gmp_and($yGmp, 1)) === 0) ? '03' : '02';
    $hex = $prefix . bin2hex($x);
    return '0x' . $hex;
  }
}

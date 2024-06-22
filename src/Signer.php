<?php

namespace Activeledger;

use Mdanter\Ecc\EccFactory;
use ParagonIE\EasyECC\EasyECC;
use ParagonIE\EasyECC\ECDSA\{PublicKey, SecretKey};

class Signer
{
  private $ecc;

  public function __construct()
  {
    $this->ecc = new EasyECC('K256');
  }

  public function sign(string $privateKeyHex, string $data)
  {
    $privateKey = $this->hexToGMP($privateKeyHex);

    $adapter = EccFactory::getAdapter();
    $generator = $this->ecc->getGenerator('K256');

    $key = new SecretKey($adapter, $generator, $privateKey);

    $sig = $this->ecc->sign($data, $key);

    $hexStr = '0x' . bin2hex($sig);

    return $hexStr;
  }

  public function verify(string $publicKey, string $data, string $signature)
  {
    $publicKey = str_replace('0x', '', $publicKey);
    $signature = str_replace('0x', '', $signature);
    $sigBin = hex2bin($signature);

    $key = PublicKey::fromString($publicKey, 'K256');

    return $this->ecc->verify($data, $key, $sigBin);
  }

  private function hexToGMP($hex)
  {
    $keystr = str_replace('0x', '', $hex);
    $gmpNum = gmp_init($keystr, 16);
    return $gmpNum;
  }
}

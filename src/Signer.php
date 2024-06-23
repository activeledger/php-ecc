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

  public function sign(string $privateKey, string $data)
  {
    $encodedData = $this->encodeData($data);
    $privateKey = str_replace('0x', '', $privateKey);
    $privateKey = $this->hexToGMP($privateKey);

    $adapter = EccFactory::getAdapter();
    $generator = $this->ecc->getGenerator('K256');

    $key = new SecretKey($adapter, $generator, $privateKey);

    $sig = $this->ecc->sign($encodedData, $key);

    $hexStr = base64_encode($sig);

    return $hexStr;
  }

  public function verify(string $publicKey, string $data, string $signature)
  {
    $encodedData = $this->encodeData($data);

    $publicKey = str_replace('0x', '', $publicKey);
    $signature = base64_decode($signature);

    $key = PublicKey::fromString($publicKey, 'K256');

    return $this->ecc->verify($encodedData, $key, $signature);
  }

  private function hexToGMP($hex)
  {
    $keystr = str_replace('0x', '', $hex);
    $gmpNum = gmp_init($keystr, 16);
    return $gmpNum;
  }

  private function encodeData(string $data)
  {
    $data = json_decode($data);
    $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $data;
  }
}

<?php

namespace Activeledger\Signature;

use Activeledger\Serialiser\PrivateKey\DerPrivateKeySerialiser;
use Activeledger\Key\PrivateKey;
use Activeledger\Maths\GmpMath;
use Activeledger\Curve\Curve;

class Signer
{
  private $signature;

  public function sign(string $privateKeyHex, string $data): string
  {
    $key = $this->constructPrivateKey($privateKeyHex);
    $sig = $this->signData($data, $key);
    $this->signature = $sig;

    return base64_encode($sig);
  }

  public function getHexSignature(): string
  {
    return '0x' . bin2hex($this->signature);
  }

  public function getBase64Signature(): string
  {
    return base64_encode($this->signature);
  }

  private function signData(string $data, PrivateKey $key): string
  {
    // Convert to PEM format for openssl_sign
    $encodedKey = $this->encodePrivateKey($key);

    // Make sure the data is in a consistent format
    $cleanData = $this->cleanData($data);

    $success = openssl_sign($cleanData, $signature, $encodedKey, OPENSSL_ALGO_SHA256);
    
    if ($success === false) {
      throw new \Exception('Failed to sign data');
    }

    return $signature;
  }

  private function cleanData(string $data): string
  {
    $cleanData = json_encode(
      json_decode($data),
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    return $cleanData;
  }

  private function encodePrivateKey(PrivateKey $key): string 
  {

    $serialiser = new DerPrivateKeySerialiser();

    $keyInfo = $serialiser->serialise($key);

    $content  = '-----BEGIN EC PRIVATE KEY-----'.PHP_EOL;
    $content .= trim(chunk_split(base64_encode($keyInfo), 64, PHP_EOL)).PHP_EOL;
    $content .= '-----END EC PRIVATE KEY-----';

    return $content;

  }

  private function constructPrivateKey(string $hex): PrivateKey {
    $keyStr = str_replace('0x', '', $hex);
    $gmpNum = gmp_init($keyStr, 16);

    $adapter = new GmpMath();
    $curve = new Curve($adapter);
    $generator = $curve->getGenerator();

    return new PrivateKey($adapter, $generator, $gmpNum);
  }

}

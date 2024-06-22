<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Activeledger\KeyGenerator;
use Activeledger\Signer;
use ParagonIE\ConstantTime\Binary;

class SignerTest extends TestCase
{
  public function testSign()
  {
    $keyGen = new KeyGenerator();
    $key = $keyGen->generateKey();
    $this->assertArrayHasKey('private', $key);
    $this->assertArrayHasKey('public', $key);
    $this->assertNotNull($key['private']);
    $this->assertNotNull($key['public']);

    $signer = new Signer();
    $data = 'Hello, World!';
    $signature = $signer->sign($key['private'], $data);
    $this->assertNotNull($signature);
    $this->assertNotEmpty($signature);
    $this->assertIsString($signature);

    // Debug printing
    echo "Signature: " . $signature . "\n";

    $verify = $signer->verify($key['public'], $data, $signature);
    $this->assertTrue($verify);

    echo "Verification: " . ($verify ? "Valid" : "Invalid") . "\n";
  }
}

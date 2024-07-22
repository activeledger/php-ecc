<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Activeledger\ActiveECC;
use PHPUnit\Framework\TestCase;

class KeyGeneratorTest extends TestCase
{
  public function testKeyGen(): void
  {
    /* $keyGen = new KeyGenerator();
    $key = $keyGen->generateKey();
    $this->assertArrayHasKey('private', $key);
    $this->assertArrayHasKey('public', $key);
    $this->assertNotNull($key['private']);
    $this->assertNotNull($key['public']);

    // Debug printing
    echo "Private Key: " . $key['private'] . "\n";
    echo "Public Key: " . $key['public'] . "\n";
    echo "\n"; */

    $keypair = ActiveECC::generate();

    $this->assertArrayHasKey('private', $keypair);
    $this->assertArrayHasKey('public', $keypair);
    $this->assertNotNull($keypair['private']);
    $this->assertNotNull($keypair['public']);

    // Debug printing
    /* echo  "Private Key: " . $keypair['private'] . "\n";
    echo  "Public Key: " . $keypair['public'] . "\n";
    echo "\n"; */

  }
}

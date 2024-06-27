<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Activeledger\KeyGenerator;

class KeyGeneratorTest extends TestCase
{
  public function testKeyGen()
  {
    $keyGen = new KeyGenerator();
    $key = $keyGen->generateKey();
    $this->assertArrayHasKey('private', $key);
    $this->assertArrayHasKey('public', $key);
    $this->assertNotNull($key['private']);
    $this->assertNotNull($key['public']);

    // Debug printing
    echo "Private Key: " . $key['private'] . "\n";
    echo "Public Key: " . $key['public'] . "\n";
    echo "\n";
  }
}

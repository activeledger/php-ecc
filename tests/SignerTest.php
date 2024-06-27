<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Activeledger\KeyGenerator;
use Activeledger\Signer;

class SignerTest extends TestCase
{
  public function testSignAndVerify()
  {
    $keyGen = new KeyGenerator();
    $key = $keyGen->generateKey();
    $this->assertArrayHasKey('private', $key);
    $this->assertArrayHasKey('public', $key);
    $this->assertNotNull($key['private']);
    $this->assertNotNull($key['public']);

    $signer = new Signer();
    $data = '{"data": "Hello, World!"}';
    $signature = $signer->sign($key['private'], $data);
    $this->assertNotNull($signature);
    $this->assertNotEmpty($signature);
    $this->assertIsString($signature);

    // Debug printing
    echo "Base64 Signature: " . $signature . "\n";

    $verify = $signer->verify($key['public'], $data, $signature);
    echo "Base64 Verification: " . ($verify ? "Valid" : "Invalid") . "\n";
    echo "\n";
    $this->assertTrue($verify);
  }

  public function testSignTx()
  {
    $keyGen = new KeyGenerator();
    $key = $keyGen->generateKey();

    $signer = new Signer();
    $data = '{
        "$namespace": "default",
        "$contract": "onboard",
        "$i": {
            "identity": {
            	"type":"secp256k1",
                "publicKey": "' . $key['public'] . '"
            }
        }
    }';

    $signature = $signer->sign($key['private'], $data);
    $this->assertNotNull($signature);
    $this->assertNotEmpty($signature);
    $this->assertIsString($signature);

    echo "Transaction signature: " . $signature . "\nFor Public key: " . $key['public'] . "\n";

    echo "Transaction body: \n" . $data . "\n";

    $verify = $signer->verify($key['public'], $data, $signature);
    echo "Transaction verification: " . ($verify ? "Valid" : "Invalid") . "\n";
    echo "\n";
  }
}

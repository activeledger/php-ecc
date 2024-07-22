<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Activeledger\ActiveECC;
use PHPUnit\Framework\TestCase;
use Activeledger\KeyGenerator;
use Activeledger\Signer;

class SignerTest extends TestCase
{
  public function testSign(): void
  {

    $activeecc = new ActiveECC();
    $key = $activeecc->generate();
    
    $this->assertArrayHasKey('private', $key);
    $this->assertArrayHasKey('public', $key);
    $this->assertNotNull($key['private']);
    $this->assertNotNull($key['public']);

    $pubKey = $key['public'];
    // echo "Public Key: " . $pubKey . "\n";

    $data = '{"data": "Hello, World!"}';
    $signature = $activeecc->sign($key['private'], $data);
    $this->assertNotNull($signature);
    $this->assertNotEmpty($signature);
    $this->assertIsString($signature);

    // Debug printing
    // echo "Base64 Signature: " . $signature . "\n";

    /* $verify = $signer->verify($key['public'], $data, $signature);
    echo "Base64 Verification: " . ($verify ? "Valid" : "Invalid") . "\n"; */
    /* echo "\n";
    $this->assertTrue($verify); */
  }

  public function testSignTx(): void
  {
    $aecc = new ActiveECC();
    $key = $aecc->generate();

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

    $signature = $aecc->sign($key['private'], $data);
    $this->assertNotNull($signature);
    $this->assertNotEmpty($signature);
    $this->assertIsString($signature);

    $hexSig = $aecc->getHexSignature();
    $this->assertNotNull($hexSig);
    $this->assertNotEmpty($hexSig);
    $this->assertIsString($hexSig);

    echo "\n";
    echo "Signed with the following private key: \n" . $key['private'] . "\n";
    echo "Transaction body: \n" . $data . "\n";
    echo "Transaction signature: " . $signature . "\nFor Public key: " . $key['public'] . "\n";
    echo "Hex Signature: " . $hexSig . "\n";


    /* $verify = $signer->verify($key['public'], $data, $signature);
    echo "Transaction verification: " . ($verify ? "Valid" : "Invalid") . "\n";
    echo "\n"; */
  }
}

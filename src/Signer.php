<?php

namespace Activeledger;
use Activeledger\Serialiser\PrivateKey\DerPrivateKeySerialiser;
use Activeledger\Signature\Hasher;
use Activeledger\Signature\SigRandomNumberGenerator;
use Activeledger\Signature\IntSigner;
use Activeledger\Signature\SignatureSerialiser;

class Signer
{

  public function sign(string $privateKeyHex, string $data): string
  {

    // $privateKey = $this->hexToGMP($privateKey);
    //
    // $adapter = EccFactory::getAdapter();
    // $generator = $this->ecc->getGenerator('K256');
    //
    // $key = new SecretKey($adapter, $generator, $privateKey);
    //
    // $sig = $this->ecc->sign($encodedData, $key);

    // $hexStr = base64_encode($sig);
    //

    $key = $this->constructPrivateKey($privateKeyHex);
    $sig = $this->signData($data, $key);
    $hexStr = '0x' . bin2hex($sig);

    return $hexStr;
  }

  private function signData(string $data, PrivateKey $key): string
  {
    $encodedKey = $this->encodePrivateKey($key);

    echo "Encoded Key: \n" . $encodedKey . "\n";

    $success = openssl_sign($data, $signature, $encodedKey, OPENSSL_ALGO_SHA256);

    // $encoded = base64_encode($serialised);
    
    echo "Signature: " . base64_encode($signature) . "\n";

    if ($success === false) {
      throw new \Exception('Failed to sign data');
    }

    return $signature;
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
    $curve = new Curve();
    $generator = $curve->getGenerator();

    return new PrivateKey($adapter, $generator, $gmpNum);
  }

  // public function verify(string $publicKey, string $data, string $signature)
  // {
  //   $encodedData = $this->encodeData($data);
  //
  //   $key = $this->constructPublicKey($publicKey);
  //   $signature = base64_decode($signature);
  //   // $key = $this->hexToGMP($publicKey);
  //
  //   $isValid = openssl_verify($encodedData, $signature, $key, OPENSSL_ALGO_SHA256);
  //   // $key = PublicKey::fromString($publicKey, 'K256');
  //   //
  //   // return $this->ecc->verify($encodedData, $key, $signature);
  //   return $isValid;
  // }

  // private function constructPublicKey(string $publicKeyHex)
  // {
  //   $publicKey = str_replace('0x', '', $publicKeyHex);
  //   $prefix = substr($publicKey, 0, 2);
  //   $x = substr($publicKey, 2, 64);
  //
  //   if ($prefix === '02' || $prefix === '03') {
  //     $x = pack('H*', $x);
  //     $y = $this->calculateY($prefix, $x);
  //   } else {
  //     throw new \Exception('Invalid public key');
  //   }
  //
  //   $keyDetails = [
  //     "key" => "EC",
  //     "curve_name" => "secp256k1",
  //     "x" => $x,
  //     "y" => $y
  //   ];
  //
  //   $key = openssl_pkey_get_details(openssl_pkey_new([
  //     "private_key_type" => OPENSSL_KEYTYPE_EC,
  //     "curve_name" => "secp256k1",
  //     "key" => $keyDetails
  //   ]))["key"];
  //
  //   return $key;
  // }
  //
  // private function calculateY(string $x, bool $isOdd)
  // {
  //   // $a = gmp_init(0);
  //   $b = gmp_init(7);
  //   $p = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
  //   $x = gmp_init($x);
  //
  //   $y2 = gmp_add(gmp_powm($x, 3, $p), $b);
  //   $y = gmp_powm($y2, gmp_div_qr(gmp_add($p, 1), 4)[0], $p);
  //
  //   if ($isOdd !== (gmp_intval(gmp_and($y, 1)) === 1)) {
  //     $y = gmp_sub($p, $y);
  //   }
  //
  //   return gmp_export($y);
  // }
  //
  // private function buildPrivateKeyFromD($d)
  // {
  //
  //   $asn1 = $this->buildASN1Sequence([
  //     $this->buildASN1Integer(hex2bin("01")), // version
  //     $this->buildASN1OctetString($d), // privateKey
  //     /* $this->buildASN1ContextSpecific(
  //       0,
  //       $this->buildASN1ObjectIdentifier("2A8648CE3D030107") // EC algorithm
  //     ) */
  //     $this->buildASN1Tag(
  //       0,
  //       $this->buildASN1ObjectIdentifier(hex2bin("2A8648CE3D030107")) // parameters (secp256k1 OID)
  //     ),
  //     $this->buildASN1Tag(
  //       1,
  //       // $this->buildASN1BitString(hex2bin("00" . bin2hex($d))) // publicKey
  //       $this->buildASN1BitString(
  //         $this->getPublicKeyFromD($d)
  //       )
  //     ),
  //   ]);
  //
  //   $privateKey = '-----BEGIN EC PRIVATE KEY-----' . "\n";
  //   $privateKey .= chunk_split(base64_encode($asn1), 64, "\n");
  //   $privateKey .= '-----END EC PRIVATE KEY-----' . "\n";
  //
  //   return $privateKey;
  // }

  /* private function getPublicKeyFromD($d)
  {
    $curve = openssl_pkey_get_details(openssl_pkey_new([
      "private_key_type" => OPENSSL_KEYTYPE_EC,
      "curve_name" => "secp256k1"
    ]));

    $x = $curve['ec']['x'];
    $y = $curve['ec']['y'];

    $point = "\x04" . bin2hex($x) . bin2hex($y);

    return hex2bin($point);
  } */

  /* private function constructPrivateKey(string $privateKeyHex)
  {
    $privateKey = str_replace('0x', '', $privateKeyHex);
    $privateKey = hex2bin($privateKey);
    $privateKey = base64_encode($privateKey);

    $pem = "-----BEGIN EC PRIVATE KEY-----\n";
    $pem .= chunk_split($privateKey, 64, "\n");
    $pem .= "-----END EC PRIVATE KEY-----\n";

    // echo "PEM:\n" . $pem . "\n";

    $key = openssl_pkey_get_private($pem);

    if ($key === false) {
      throw new \Exception('Invalid private key');
    }

    return $key;
  }

  private function hexToGMP($hex)
  {
    $keystr = str_replace('0x', '', $hex);
    $gmpNum = gmp_init($keystr, 16);
    return $gmpNum;
  } */

  // private function encodeData(string $data)
  // {
  //   $data = json_decode($data);
  //   $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  //   return $data;
  // }
  //
  // private function buildASN1Sequence($elements)
  // {
  //   $data = implode('', $elements);
  //   return "\x30" . $this->encodeLength(strlen($data)) . $data;
  // }
  //
  // private function buildASN1Integer($value)
  // {
  //   return "\x02" . $this->encodeLength(strlen($value)) . $value;
  // }
  //
  // private function buildASN1OctetString($value)
  // {
  //   return "\x04" . $this->encodeLength(strlen($value)) . $value;
  // }
  //
  // private function buildASN1ContextSpecific($tagNumber, $value)
  // {
  //   return chr(0xA0 + $tagNumber) . $this->encodeLength(strlen($value)) . $value;
  // }
  //
  // private function buildASN1ObjectIdentifier($value)
  // {
  //   return "\x06" . $this->encodeLength(strlen($value)) . $value;
  //   /* $binaryOid = '';
  //   $parts = explode('.', $oid);
  //   $binaryOid .= chr(40 * $parts[0] + $parts[1]);
  //   for ($i = 2; $i < count($parts); $i++) {
  //     $binaryOid .= $this->encodeBase128($parts[$i]);
  //   }
  //   return "\x06" . $this->encodeLength(strlen($binaryOid)) . $binaryOid; */
  // }
  //
  // private function buildASN1BitString($value)
  // {
  //   return "\x03" . $this->encodeLength(strlen($value)) . $value;
  // }
  //
  // private function buildASN1Tag($tagNumber, $value)
  // {
  //   return chr(0xA0 + $tagNumber) . $this->encodeLength(strlen($value)) . $value;
  // }
  //
  // private function encodeBase128($value)
  // {
  //   $result = '';
  //   while ($value > 0) {
  //     $result = chr(($value & 0x7F) | 0x80) . $result;
  //     $value >>= 7;
  //   }
  //   $result[strlen($result) - 1] = $result[strlen($result) - 1] & "\x7F";
  //   return $result;
  // }
  //
  // private function encodeLength($length)
  // {
  //   if ($length < 128) {
  //     return chr($length);
  //   }
  //
  //   $lenBytes = '';
  //   while ($length > 0) {
  //     $lenBytes = chr($length & 0xFF) . $lenBytes;
  //     $length >>= 8;
  //   }
  //
  //   return chr(0x80 | strlen($lenBytes)) . $lenBytes;
  // }
  //
  // private function getPublicKeyFromD($d)
  // {
  //   // Use the private key d to compute the public key point (x, y) on the secp256k1 curve
  //   $p = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16);
  //   $a = gmp_init(0, 10);
  //   $b = gmp_init(7, 10);
  //   $gx = gmp_init('0x79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 16);
  //   $gy = gmp_init('0x483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8', 16);
  //   $g = [$gx, $gy];
  //   $n = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
  //
  //   $point = $this->ecMultiply($d, $g, $a, $b, $p);
  //   $x = str_pad(gmp_strval($point[0], 16), 64, '0', STR_PAD_LEFT);
  //   $y = str_pad(gmp_strval($point[1], 16), 64, '0', STR_PAD_LEFT);
  //
  //   return hex2bin('04' . $x . $y);
  // }
  //
  // private function ecMultiply($k, $p, $a, $b, $mod)
  // {
  //   $n = $p;
  //   $q = [gmp_init(0), gmp_init(0)];
  //   while (gmp_cmp($k, gmp_init(0)) > 0) {
  //     if (gmp_testbit($k, 0)) {
  //       $q = $this->ecAdd($q, $n, $a, $b, $mod);
  //     }
  //     $n = $this->ecAdd($n, $n, $a, $b, $mod);
  //     $k = gmp_div_q($k, 2);
  //   }
  //   return $q;
  // }
  //
  // private function ecAdd($p, $q, $a, $b, $mod)
  // {
  //   if (gmp_cmp($p[0], gmp_init(0)) == 0 && gmp_cmp($p[1], gmp_init(0)) == 0) {
  //     return $q;
  //   }
  //   if (gmp_cmp($q[0], gmp_init(0)) == 0 && gmp_cmp($q[1], gmp_init(0)) == 0) {
  //     return $p;
  //   }
  //   if (gmp_cmp($p[0], $q[0]) == 0) {
  //     if (gmp_cmp($p[1], $q[1]) == 0) {
  //       return $this->ecDouble($p, $a, $b, $mod);
  //     } else {
  //       return [gmp_init(0), gmp_init(0)];
  //     }
  //   }
  //   $s = gmp_div_q(gmp_sub($p[1], $q[1]), gmp_sub($p[0], $q[0]));
  //   $rx = gmp_mod(gmp_sub(gmp_sub(gmp_pow($s, 2), $p[0]), $q[0]), $mod);
  //   $ry = gmp_mod(gmp_sub(gmp_mul($s, gmp_sub($p[0], $rx)), $p[1]), $mod);
  //   return [$rx, $ry];
  // }
  //
  // private function ecDouble($p, $a, $b, $mod)
  // {
  //   $s = gmp_div_q(gmp_add(gmp_mul(3, gmp_pow($p[0], 2)), $a), gmp_mul(2, $p[1]));
  //   $rx = gmp_mod(gmp_sub(gmp_pow($s, 2), gmp_mul(2, $p[0])), $mod);
  //   $ry = gmp_mod(gmp_sub(gmp_mul($s, gmp_sub($p[0], $rx)), $p[1]), $mod);
  //   return [$rx, $ry];
  // }
}

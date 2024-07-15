<?php

namespace Activeledger\Signature;

use Activeledger\GmpMath;
use Activeledger\GeneratorPoint;
use GMP;

class Hasher {

  private $adapter;

  public function __construct(GmpMath $adapter = null)
  {
    if ($adapter === null) {
      $adapter = new GmpMath();
    }
    $this->adapter = $adapter;
  }

  public function hash(
    #[\SensitiveParameter]
    string $data,
    #[\SensitiveParameter]
    GeneratorPoint $g
  ): GMP
  {
    $rawHash = hash('sha256', $data);
    /** @var GMP $hash */
    $hash = gmp_init(
      $rawHash,
      16
    );

    return $this->truncate($hash, $g);
  }

  private function truncate(
    #[\SensitiveParameter]
    GMP $hash, 
    #[\SensitiveParameter]
    GeneratorPoint $g
  ): GMP
  {
    
    $bits = gmp_strval($hash, 2);
    $binaryLen = $this->binaryLength($bits);

    $algoSize = 32; // SHA256

    if ($binaryLen < $algoSize * 8) {
      $bits = str_pad($bits, $algoSize * 8, '0', STR_PAD_LEFT);
    }

    $binaryNumbits = $this->bnNumBits($g->getOrder());

    $binarySubstr = $this->binarySubstring($bits, 0, $binaryNumbits);
    /** @var GMP $obj */
    $obj = gmp_init($binarySubstr, 2);

    return $obj;

  }

  private function binaryLength(
    #[\SensitiveParameter]
    string $str
  ): int {

    static $exists = null;

    if ($exists === null) {
      $exists = function_exists('mb_strlen');
    }

    // If mb_strlen exists, use it and make sure to use 8bit mode
    if ($exists) {
      return mb_strlen($str, '8bit');
    }

    return strlen($str);
  }

  private function binarySubstring(
    #[\SensitiveParameter]
    string $str,
    int $start = 0,
    int $length = null
  ): string {

    static $exists = null;
    if ($exists === null) {
      $exists = function_exists('mb_substr');
    }

    // If mb_substr exists, use it and make sure to use 8bit mode
    if ($exists) {
      return mb_substr($str, $start, $length, '8bit');
    } elseif ($length !== null) {
      return substr($str, $start, $length);
    }

    return substr($str, $start);
  }

  private function bnNumBits(
    #[\SensitiveParameter]
    GMP $x
  ): int {
    /** @var GMP $zero */
    $zero = gmp_init(0, 10);
    if ($this->adapter->equals($x, $zero)) {
        return 0;
    }

    $log2 = 0;
    while (false === $this->adapter->equals($x, $zero)) {
        $x = $this->adapter->rightShift($x, 1);
        $log2++;
    }

    return $log2 ;
  }

}

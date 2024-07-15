<?php

namespace Activeledger\Signature;

use GMP;
use RangeException;
use Exception;

use Activeledger\GmpMath;
use Activeledger\PrivateKey;

class SigRandomNumberGenerator
{
  private $math;
  private $privateKey;
  private $messageHash;

  public function __construct(
    GmpMath $math,
    PrivateKey $privateKey,
    GMP $messageHash
  )
  {
    $this->math = $math;
    $this->privateKey = $privateKey;
    $this->messageHash = $messageHash;
  }

  public function generate(GMP $max): GMP
  {
    $qlen = gmp_init($this->bnNumBits($max), 10);
    $rlen = $this->math->rightShift($this->math->add($qlen, gmp_init(7, 10)), 3);

    // INFO: Using SHA384 as the algorithm
    $algorithm = 'sha384';
    $hlen = 384;

    $bx = $this->int2octets($this->privateKey->getSecret(), $rlen) . $this->int2octets($this->messageHash, $rlen);
    // This is the hedged part:
    $bx .= random_bytes(32);

    $v = str_pad('', $hlen >> 3, "\x01", STR_PAD_LEFT);
    $k = str_pad('', $hlen >> 3, "\x00", STR_PAD_LEFT);


    $k = hash_hmac($algorithm, $v . "\x00" . $bx, $k, true);
    $v = hash_hmac($algorithm, $v, $k, true);

    $k = hash_hmac($algorithm, $v . "\x01" . $bx, $k, true);
    $v = hash_hmac($algorithm, $v, $k, true);

    $t = '';
    for ($tries = 0; $tries < 1024; ++$tries) {
        $toff = gmp_init(0, 10);
        while ($this->math->cmp($toff, $rlen) < 0) {
            $v = hash_hmac($algorithm, $v, $k, true);

            $cc = min($this->binaryLength($v), (int) gmp_strval(gmp_sub($rlen, $toff), 10));
            $t .= $this->binarySubstring($v, 0, $cc);
            $toff = gmp_add($toff, $cc);
        }
        $k = $this->bits2int($t, $qlen);
        if ($this->math->cmp($k, gmp_init(0, 10)) > 0 && $this->math->cmp($k, $max) < 0) {
            return $k;
        }

        $k = $this->decodeHex(gmp_strval($k, 16));
        $k = hash_hmac($algorithm, $v . "\x00", $k, true);
        $v = hash_hmac($algorithm, $v, $k, true);
    }
    throw new Exception('Infinite loop breached');
  }

  private function decodeHex(
    #[\SensitiveParameter]
    string $encodedString,
    bool $strictPadding = false
  ): string {
    $hex_pos = 0;
    $bin = '';
    $c_acc = 0;
    $hex_len = $this->binarySafeStrLen($encodedString);
    $state = 0;
    if (($hex_len & 1) !== 0) {
      if ($strictPadding) {
        throw new RangeException(
            'Expected an even number of hexadecimal characters'
        );
      } else {
        $encodedString = '0' . $encodedString;
        ++$hex_len;
      }
    }

    /** @var array<int, int> $chunk */
    $chunk = \unpack('C*', $encodedString);
    while ($hex_pos < $hex_len) {
      ++$hex_pos;
      $c = $chunk[$hex_pos];
      $c_num = $c ^ 48;
      $c_num0 = ($c_num - 10) >> 8;
      $c_alpha = ($c & ~32) - 55;
      $c_alpha0 = (($c_alpha - 10) ^ ($c_alpha - 16)) >> 8;

      if (($c_num0 | $c_alpha0) === 0) {
        throw new RangeException(
          'Expected hexadecimal character'
        );
      }
      $c_val = ($c_num0 & $c_num) | ($c_alpha & $c_alpha0);
      if ($state === 0) {
        $c_acc = $c_val * 16;
      } else {
        $bin .= \pack('C', $c_acc | $c_val);
      }
      $state ^= 1;
    }
    return $bin;
  }

  private function binarySafeStrLen(
    #[\SensitiveParameter]
    string $str
  ): int {
    if (function_exists('mb_strlen')) {
      return mb_strlen($str, '8bit');
    }

    return strlen($str);
  }

  private function bits2int(string $bits, GMP $qlen): GMP
  {
    $vlen = gmp_init($this->binaryLength($bits) * 8, 10);
    $hex = bin2hex($bits);
    $v = gmp_init($hex, 16);

    if ($this->math->cmp($vlen, $qlen) > 0) {
        $v = $this->math->rightShift($v, (int) $this->math->toString($this->math->sub($vlen, $qlen)));
    }

    return $v;
  }

  private function int2octets(GMP $int, GMP $rlen): string 
  {
     $out = pack("H*", $this->math->decHex(gmp_strval($int, 10)));
        $length = gmp_init($this->binaryLength($out), 10);
        if ($this->math->cmp($length, $rlen) < 0) {
            return str_pad('', (int) $this->math->toString($this->math->sub($rlen, $length)), "\x00") . $out;
        }

        if ($this->math->cmp($length, $rlen) > 0) {
            return $this->binarySubstring($out, 0, (int) $this->math->toString($rlen));
        }

        return $out;
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
    if ($this->math->equals($x, $zero)) {
        return 0;
    }

    $log2 = 0;
    while (false === $this->math->equals($x, $zero)) {
        $x = $this->math->rightShift($x, 1);
        $log2++;
    }

    return $log2 ;
  }
}

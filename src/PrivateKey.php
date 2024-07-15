<?php

namespace Activeledger;

use GMP;

class PrivateKey 
{
  private $adapter;
  private $generator;
  private $secretMultiplier;

  public function __construct(
    GmpMath $adapter,
    GeneratorPoint $generator,
    #[\SensitiveParameter]
    GMP $secretMultiplier) {
    $this->adapter = $adapter;
    $this->generator = $generator;
    $this->secretMultiplier = $secretMultiplier;
  }

  public static function generate(): self {
    $curve = new Curve();
    $generator = $curve->getGenerator();
    $sk = $generator->createPrivateKey();

    $adapter = new GmpMath();

    return new self($adapter, $generator, $sk->getSecret());
  }

  public function getGenerator(): GeneratorPoint {
    return $this->generator;
  }

  public function getSecret(): GMP {
    return $this->secretMultiplier;
  }

  public function getPublicKey(): PublicKey {
    return new PublicKey($this->adapter, $this->generator, $this->generator->mul($this->secretMultiplier));
  }
}

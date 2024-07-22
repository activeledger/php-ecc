<?php

namespace Activeledger;

use Activeledger\Key\KeyGenerator;
use Activeledger\Signature\Signer;

class ActiveECC {

  private $signer;

  /**
   * @return string[]
   */
  public static function generate(): array {
    return KeyGenerator::generateKey();
  }

  public function sign(string $privateKey, string $data): string {
    $this->signer = new Signer();
    return $this->signer->sign($privateKey, $data);
  }

  public function getHexSignature(): string {
    return $this->signer->getHexSignature();
  } 

  public function getBase64Signature(): string {
    return $this->signer->getBase64Signature();
  }

}

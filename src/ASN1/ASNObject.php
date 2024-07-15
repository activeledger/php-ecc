<?php

namespace Activeledger\ASN1;

use LogicException;

use Activeledger\ASN1\Identifier;

abstract class ASNObject
{
  private $contentLength;
  private $nrOfLengthOctets;

  abstract public function getType(): int;

  abstract public function getContent(): mixed;

  abstract protected function getEncodedValue(): string;

  abstract protected function calculateContentLength(): int;

  public function getBinary(): string
  {
    $result = $this->getIdentifier();
    $result .= $this->createLengthPart();
    $result .= $this->getEncodedValue();

    return $result;
  }

  public function getObjectLength(): int
  {
    $numberOfIdentifierOctets = strlen($this->getIdentifier());
    $contentLength = $this->getContentLength();
    $numberOfLengthOctets = $this->getNumberOfLengthOctets($contentLength);

    return $numberOfIdentifierOctets + $numberOfLengthOctets + $contentLength;
  }

  public function __toString(): string
  {
    return (string)$this->getContent();
  }

  private function getIdentifier(): string
  {
    $firstOctet = $this->getType();

        if (Identifier::isLongForm($firstOctet)) {
            throw new LogicException(sprintf('Identifier of %s uses the long form and must therefor override "ASNObject::getIdentifier()".', get_class($this)));
        }

        return chr($firstOctet);
  }

  private function createLengthPart(): string
  {
    $contentLength = $this->getContentLength();
        $nrOfLengthOctets = $this->getNumberOfLengthOctets($contentLength);

        if ($nrOfLengthOctets == 1) {
            return chr($contentLength);
        } else {
            // the first length octet determines the number subsequent length octets
            $lengthOctets = chr(0x80 | ($nrOfLengthOctets - 1));
            for ($shiftLength = 8 * ($nrOfLengthOctets - 2); $shiftLength >= 0; $shiftLength -= 8) {
                $lengthOctets .= chr($contentLength >> $shiftLength);
            }

            return $lengthOctets;
        }
  }

  private function getContentLength(): int
  {
    if (!isset($this->contentLength)) {
            $this->contentLength = $this->calculateContentLength();
        }

        return $this->contentLength;
  }

  protected function getNumberOfLengthOctets(): int
  {

    if (!isset($this->nrOfLengthOctets)) {
            if ($contentLength == null) {
                $contentLength = $this->getContentLength();
            }

            $this->nrOfLengthOctets = 1;
            if ($contentLength > 127) {
                do { // long form
                    $this->nrOfLengthOctets++;
                    $contentLength = $contentLength >> 8;
                } while ($contentLength > 0);
            }
        }

        return $this->nrOfLengthOctets;
  }
}

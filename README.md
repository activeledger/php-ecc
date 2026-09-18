<img src="https://www.activeledger.io/wp-content/uploads/2018/09/Asset-23.png" alt="Activeledger" width="500"/>

# Activeledger - PHP Key SDK

> ## ⚠️ Unmaintained and archived — use [SDK-PHP](https://github.com/activeledger/SDK-PHP) instead
>
> This repository existed because the main PHP SDK could not do secp256k1.
> **It can now**, and its implementation is materially better than this one.
>
> Do not copy the signing code here. It predates two things the ledger's
> ecosystem depends on:
>
> - **It does not normalise S to the lower half of the curve order.** Roughly
>   half of its signatures are therefore high-S. The ledger accepts those, but
>   `@noble/curves` — the reference for the JavaScript side — rejects them by
>   default, as do libsecp256k1 and Rust's `k256`. A signer emitting high-S
>   fails against such a verifier about half the time, which reads as flaky
>   auth rather than as a signature problem.
> - **It is not RFC 6979 deterministic**, so its output cannot be compared
>   against the published cross-language test vectors.
>
> [SDK-PHP](https://github.com/activeledger/SDK-PHP) does both, is checked against
> published reference bytes on every test run, and covers post-quantum
> identities as well.



The Activeledger PHP Key SDK has been built to provide an easy way to generate an ECC keypair that can be used to sign transactions to be 
sent to the Activeledger network.

### Activeledger

[Visit Activeledger.io](https://activeledger.io/)

[Read Activeledgers documentation](https://github.com/activeledger/activeledger)

## Installation

```
$ composer require activeledger/ecc ~0.0.1
$ composer install -o
```

## Usage

The SDK currently supports the following functions:
* Generate a new ECC keypair
* Sign a string using the generated private key

### Generate a new ECC keypair

The generate method returns an array containing the public and private keys as HEX strings.

```php
<?php
use Activeledger\ActiveECC;

class MyCoolClass
{
    public function generateKeyPair(): array
    {
        $ecc = new ActiveECC();
        $keyPair = $ecc->generate();

        // Alternatively call generate as a static method
        $keyPair = ActiveECC::generate();

        return $keyPair;
    }
}
```

### Sign a string using a private key

The sign method takes two parameters: the private key as a HEX string, and the data to be signed also as a string.

```php
<?php
use Activeledger\ActiveECC;

class MyCoolClass
{
    public function signString(string $privateKey, string $data)
    {
        $ecc = new ActiveECC();
        $signature = $ecc->sign($privateKey, $string);
        return $signature;
    }
}
```

## License

---

This project is licensed under the [MIT](https://github.com/activeledger/activeledger/blob/master/LICENSE) License

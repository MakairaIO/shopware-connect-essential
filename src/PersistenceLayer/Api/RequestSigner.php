<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Api;

final readonly class RequestSigner
{
    public function __construct(protected string $sharedSecret)
    {
    }

    public function sign(string $nonce, string $body): string
    {
        return hash_hmac('sha256', $nonce . ':' . $body, $this->sharedSecret);
    }
}

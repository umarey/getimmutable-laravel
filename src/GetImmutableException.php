<?php

namespace GetImmutable;

use RuntimeException;

class GetImmutableException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $responseBody = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}

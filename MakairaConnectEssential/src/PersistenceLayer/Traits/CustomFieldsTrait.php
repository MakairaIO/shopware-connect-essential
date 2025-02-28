<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Traits;

trait CustomFieldsTrait
{
    private function processCustomFields(?array $customFields): array
    {
        return array_map(
            fn ($value) => \is_string($value)
                ? json_encode(json_decode($value, true), \JSON_UNESCAPED_UNICODE)
                : $value,
            $customFields ?? []
        );
    }
}

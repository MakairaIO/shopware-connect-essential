<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Traits;

trait CustomFieldsTrait
{
    private function processCustomFields(?array $customFields): array
    {
        return array_map(
            function ($value) {
                if (\is_string($value)) {
                    // Attempt to decode JSON strings
                    $decoded = json_decode($value, true);
                    return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
                }

                if (\is_array($value)) {
                    // Recursively process arrays
                    return $this->processCustomFields($value);
                }

                // Return other types (int, float, bool) as-is
                return $value;
            },
            $customFields ?? []
        );
    }
}

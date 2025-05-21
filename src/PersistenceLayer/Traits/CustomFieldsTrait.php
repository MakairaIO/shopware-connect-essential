<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Traits;

use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait CustomFieldsTrait
{
    private ?PluginConfig $customFieldsPluginConfig = null;

    public function setPluginConfig(PluginConfig $pluginConfig): void
    {
        $this->customFieldsPluginConfig = $pluginConfig;
    }

    private function processCustomFields(?array $customFields, ?SalesChannelContext $salesChannelContext = null): array
    {
        if (empty($customFields)) {
            return [];
        }

        $ignoreList = [];
        if ($this->customFieldsPluginConfig !== null && $salesChannelContext !== null) {
            $ignoreList = $this->customFieldsPluginConfig->getCustomFieldsIgnoreList($salesChannelContext->getSalesChannelId());
        }

        $result = [];
        foreach ($customFields as $key => $value) {
            // Skip fields that are in the ignore list
            if (in_array($key, $ignoreList, true)) {
                continue;
            }

            if (\is_string($value)) {
                // Attempt to decode JSON strings
                $decoded      = json_decode($value, true);
                $result[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            } elseif (\is_array($value)) {
                // Recursively process arrays
                $result[$key] = $this->processCustomFields($value, $salesChannelContext);
            } else {
                // Return other types (int, float, bool) as-is
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

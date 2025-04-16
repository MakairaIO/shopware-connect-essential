<?php

declare(strict_types=1);

namespace MakairaConnectEssential;

use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;

class MakairaConnectEssential extends Plugin
{
    public const PLUGIN_VERSION = '1.0.2';

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);

        $salesChannelLoader  = $this->container->get('MakairaConnectEssential\Loader\SalesChannelLoader');
        $systemConfigService = $this->container->get('Shopware\Core\System\SystemConfig\SystemConfigService');

        $allSalesChannelIds = $salesChannelLoader->getAllIds(Context::createDefaultContext(), null);
        foreach ($allSalesChannelIds as $salesChannelId) {
            $systemConfigService->set(PluginConfig::KEY_PREFIX . PluginConfig::MAKAIRA_SHARED_SECRET, '', $salesChannelId);
            $systemConfigService->set(PluginConfig::KEY_PREFIX . PluginConfig::MAKAIRA_INSTANCE, '', $salesChannelId);
        }
    }
}

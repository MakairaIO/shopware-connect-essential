<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Subscriber;

use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Importer\ProductImporter;
use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected PluginConfig $pluginConfig,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected ProductImporter $productImporter
    ) {
    }
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
            ProductEvents::PRODUCT_DELETED_EVENT => 'onProductDeleted',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        $productIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->productImporter->upsert($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $productIds);
        }
    }

    public function onProductDeleted(EntityDeletedEvent $event)
    {
        $productIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->productImporter->delete($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $productIds);
        }
    }
}

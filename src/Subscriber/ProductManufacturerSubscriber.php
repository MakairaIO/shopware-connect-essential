<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Subscriber;

use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Importer\ProductManufacturerImporter;
use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductManufacturerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected PluginConfig $pluginConfig,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected ProductManufacturerImporter $productManufacturerImporter
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_MANUFACTURER_WRITTEN_EVENT => 'onProductManufacturerWritten',
            ProductEvents::PRODUCT_MANUFACTURER_DELETED_EVENT => 'onProductManufacturerDeleted',
        ];
    }

    public function onProductManufacturerWritten(EntityWrittenEvent $event): void
    {
        $productManufacturerIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->productManufacturerImporter->upsert($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $productManufacturerIds);
        }
    }

    public function onProductManufacturerDeleted(EntityDeletedEvent $event): void
    {
        $productManufacturerIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->productManufacturerImporter->delete($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $productManufacturerIds);
        }
    }
}
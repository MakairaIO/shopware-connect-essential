<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Subscriber;

use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Importer\CategoryImporter;
use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Content\Category\CategoryEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CategorySubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected PluginConfig $pluginConfig,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected CategoryImporter $categoryImporter
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CategoryEvents::CATEGORY_WRITTEN_EVENT => 'onCategoryWritten',
            CategoryEvents::CATEGORY_DELETED_EVENT => 'onCategoryDeleted',
        ];
    }

    public function onCategoryWritten(EntityWrittenEvent $event): void
    {
        $categoryIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->categoryImporter->upsert($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $categoryIds);
        }
    }

    public function onCategoryDeleted(EntityDeletedEvent $event): void
    {
        $categoryIds = $event->getIds();

        $salesChannelIds = $this->salesChannelLoader->getAllIds($event->getContext(), true);

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $this->categoryImporter->delete($salesChannelContext, $this->pluginConfig->createMakairaApiConfig($salesChannelId), $categoryIds);
        }
    }
}
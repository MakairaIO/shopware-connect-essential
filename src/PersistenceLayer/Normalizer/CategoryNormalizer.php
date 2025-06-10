<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Normalizer;

use MakairaConnectEssential\Events\ModifierQueryRequestEvent;
use MakairaConnectEssential\Loader\CategoryLoader;
use MakairaConnectEssential\PersistenceLayer\Traits\CustomFieldsTrait;
use MakairaConnectEssential\PersistenceLayer\Traits\UrlTrait;
use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CategoryNormalizer implements NormalizerInterface
{
    use CustomFieldsTrait;
    use UrlTrait;

    public function __construct(
        protected CategoryLoader $categoryLoader,
        private EventDispatcherInterface $eventDispatcher,
        PluginConfig $pluginConfig
    ) {
        $this->setPluginConfig($pluginConfig);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (! $object instanceof CategoryEntity) {
            return null;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $context['salesChannelContext'];

        $data = [
            'id'               => $object->getId(),
            'type'             => 'category',
            'shop'             => 1, // use legacy shop ID for compatibility
            'category_title'   => $object->getTranslation('name'),
            'level'            => $object->getLevel(),
            'parent'           => $object->getParentId() ?? '',
            'subcategories'    => $this->categoryLoader->getSubcategories($object->getId(), $salesChannelContext),
            'hierarchy'        => $this->getHierarchy($object),
            'description'      => $object->getTranslation('description'),
            'metaTitle'        => $object->getTranslation('metaTitle'),
            'metaDescription'  => $object->getTranslation('metaDescription'),
            'keywords'         => $object->getTranslation('keywords'),
            'customFields'     => $this->processCustomFields($object->getCustomFields(), $salesChannelContext),
            'active'           => $object->getActive(),
            'hidden'           => !$object->getVisible(),
            'images'           => $object->getMedia() ? ['/' . $object->getMedia()->getPath()] : null,
            'url'              => '/' . $this->getSeoUrlPath($object->getSeoUrls(), $salesChannelContext->getLanguageId()),
            'timestamp'        => ($object->getUpdatedAt() ?? $object->getCreatedAt())->format('Y-m-d H:i:s'),
        ];

        // Dispatch the ModifierQueryRequestEvent for categories
        $event = new ModifierQueryRequestEvent($data);
        $this->eventDispatcher->dispatch($event, ModifierQueryRequestEvent::NAME_CATEGORY);

        // Return the potentially modified data
        return $event->getQuery()->getArrayCopy();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CategoryEntity && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CategoryEntity::class => true,
        ];
    }

    protected function getHierarchy(CategoryEntity $category): string
    {
        $hierarchy   = null !== $category->getPath() ? \array_slice(explode('|', $category->getPath()), 1, -1) : [];
        $hierarchy[] = $category->getId();

        return implode('//', $hierarchy);
    }
}

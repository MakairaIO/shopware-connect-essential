<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Normalizer;

use MakairaConnectEssential\Loader\CategoryLoader;
use MakairaConnectEssential\PersistenceLayer\Traits\CustomFieldsTrait;
use MakairaConnectEssential\PersistenceLayer\Traits\MediaTrait;
use MakairaConnectEssential\PersistenceLayer\Traits\UrlTrait;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CategoryNormalizer implements NormalizerInterface
{
    use CustomFieldsTrait;
    use MediaTrait;
    use UrlTrait;

    public function __construct(protected CategoryLoader $categoryLoader)
    {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (! $object instanceof CategoryEntity) {
            return null;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $context['salesChannelContext'];

        return [
            'id'              => $object->getId(),
            'type'            => 'category',
            'shop'            => intval($salesChannelContext->getSalesChannelId()),
            'category_title'  => $object->getTranslation('name'),
            'level'           => $object->getLevel(),
            'parent'          => $object->getParentId() ?? '',
            'subcategories'   => $this->categoryLoader->getSubcategories($object->getId(), $salesChannelContext),
            'hierarchy'       => $this->getHierarchy($object),
            'description'     => $object->getTranslation('description'),
            'metaTitle'       => $object->getTranslation('metaTitle'),
            'metaDescription' => $object->getTranslation('metaDescription'),
            'keywords'        => $object->getTranslation('keywords'),
            'customFields'    => $this->processCustomFields($object->getCustomFields()),
            'active'          => $object->getActive(),
            'hidden'          => !$object->getVisible(),
            'image'           => $this->processMedia($object->getMedia()),
            'url'             => '/' . $this->getSeoUrlPath($object->getSeoUrls(), $salesChannelContext->getLanguageId()),
            'timestamp'       => ($object->getUpdatedAt() ?? $object->getCreatedAt())->format('Y-m-d H:i:s'),
        ];
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

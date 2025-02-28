<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Normalizer;

use MakairaConnectEssential\PersistenceLayer\Traits\CustomFieldsTrait;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tag\TagEntity;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNormalizer implements NormalizerInterface
{
    use CustomFieldsTrait;

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (! $object instanceof SalesChannelProductEntity) {
            return null;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $context['salesChannelContext'];

        return [
            'id' => $object->getId(),
            'type' => $object->getParentId() !== null ? 'variant' : 'product',
            'parent' => $object->getParentId() ?? '',
            'isVariant' => null !== $object->getParentId(),
            'shop' => intval($salesChannelContext->getSalesChannelId()),
            'ean' => $object->getEan() ?? '',
            'active' => (bool) $object->getActive(),
            'stock' => $object->getAvailableStock(),
            'onstock' => 0 < $object->getAvailableStock(),
            'productNumber' => $object->getProductNumber(),
            'title' => $object->getTranslation('name'),
            'longdesc' => $object->getTranslation('description'),
            'keywords' => $object->getTranslation('keywords'),
            'meta_title' => $object->getTranslation('metaTitle'),
            'meta_description' => $object->getTranslation('metaDescription'),
            //'attributeStr' => $this->getGroupedOptions($entity->getProperties(), $entity->getOptions()),
            //'category' => array_values($categories),
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
            'length' => $object->getLength(),
            'weight' => $object->getWeight(),
            'packUnit' => $object->getTranslation('packUnit'),
            'packUnitPlural' => $object->getTranslation('packUnitPlural'),
            'referenceUnit' => $object->getReferenceUnit(),
            'purchaseUnit' => $object->getPurchaseUnit(),
            'manufacturerid' => $object->getManufacturerId(),
            'manufacturer_title' => $object->getManufacturer()?->getName(),
            'ratingAverage' => $object->getRatingAverage(),
            //'totalProductReviews' => $this->countProductReviews($entity->getId(), $context->getContext()),
            'customFields' => $this->processCustomFields($object->getCustomFields()),
            'topseller' => $object->getMarkAsTopseller(),
            'searchable' => true,
            //'searchkeys' => $this->getSearchKeys($entity),
            'tags' => $object->getTags()?->map(fn (TagEntity $tag): string => $tag->getName()),
            'unit' => $object->getUnit()?->getShortCode(),
            'price' => $object->getCalculatedPrice()->getUnitPrice(),
            'referencePrice' => $object->getCalculatedPrice()->getReferencePrice()?->getPrice(),
            'images' => [],
            //'images' => array_values($images),
            //'url' => $this->urlGenerator->generate($entity, $context),
            'timestamp' => ($object->getUpdatedAt() ?? $object->getCreatedAt())->format('Y-m-d H:i:s'),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ProductEntity && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ProductEntity::class => true,
        ];
    }
}
<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Normalizer;

use MakairaConnectEssential\Events\ModifierQueryRequestEvent;
use MakairaConnectEssential\Loader\CategoryLoader;
use MakairaConnectEssential\PersistenceLayer\Traits\CustomFieldsTrait;
use MakairaConnectEssential\PersistenceLayer\Traits\UrlTrait;
use MakairaConnectEssential\Utils\PluginConfig;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tag\TagEntity;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProductNormalizer implements NormalizerInterface
{
    use CustomFieldsTrait;
    use UrlTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private CategoryLoader $categoryLoader,
        PluginConfig $pluginConfig
    ) {
        $this->setPluginConfig($pluginConfig);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (! $object instanceof SalesChannelProductEntity) {
            return null;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $context['salesChannelContext'];

        $categoryDataById = [];

        foreach ($object->getCategories() as $category) {
            // Load the category
            $category = $this->categoryLoader->loadByIds([$category->getId()], $salesChannelContext)->first();
            if (! $category instanceof CategoryEntity) {
                continue;
            }
            // Add the main category
            $catid = $category->getId();
            if (!isset($categoryDataById[$catid])) {
                $categoryDataById[$catid] = [
                    'catid'  => $catid,
                    'title'  => $category->getName(),
                    'shopid' => intval($salesChannelContext->getSalesChannelId()),
                    'pos'    => 0,
                    'path'   => '/' . $this->getSeoUrlPath($category->getSeoUrls(), $salesChannelContext->getLanguageId()),
                ];
            }
            // Add parent categories from path
            if ($category->getPath()) {
                $pathIds          = array_values(array_filter(explode('|', $category->getPath())));
                $parentCategories = $this->categoryLoader->loadByIds($pathIds, $salesChannelContext);
                foreach ($parentCategories as $parentCategory) {
                    if ($parentCategory instanceof CategoryEntity) {
                        $parentCatid = $parentCategory->getId();
                        if (!isset($categoryDataById[$parentCatid])) {
                            $categoryDataById[$parentCatid] = [
                                'catid'  => $parentCatid,
                                'title'  => $parentCategory->getTranslation('name'),
                                'shopid' => intval($salesChannelContext->getSalesChannelId()),
                                'pos'    => 0,
                                'path'   => '/' . $this->getSeoUrlPath($parentCategory->getSeoUrls(), $salesChannelContext->getLanguageId()),
                            ];
                        }
                    }
                }
            }
        }

        $mergedArray = array_values($categoryDataById);

        $mediaUrls = [];
        $index     = 0;
        if ($object->getMedia()) {
            foreach ($object->getMedia() as $productMedia) {
                if (($url = $productMedia->getMedia()?->getPath())) {
                    $mediaUrls[$index++] = '/' . $url;
                }
            }
        }

        $data = [
            'id'                  => $object->getId(),
            'type'                => $object->getParentId() !== null ? 'variant' : 'product',
            'parent'              => $object->getParentId() ?? '',
            'isVariant'           => null !== $object->getParentId(),
            'shop'                => intval($salesChannelContext->getSalesChannelId()),
            'ean'                 => $object->getEan() ?? $object->getProductNumber() ?? '',
            'active'              => (bool) $object->getActive(),
            'stock'               => $object->getAvailableStock(),
            'onstock'             => 0 < $object->getAvailableStock(),
            'productNumber'       => $object->getProductNumber(),
            'title'               => $object->getTranslation('name'),
            'longdesc'            => $object->getTranslation('description'),
            'keywords'            => $object->getTranslation('keywords'),
            'meta_title'          => $object->getTranslation('metaTitle'),
            'meta_description'    => $object->getTranslation('metaDescription'),
            'attributeStr'        => $this->getGroupedOptions($object->getProperties(), $object->getOptions()),
            'category'            => $mergedArray,
            'maincategory'        => $object->getCategories()->first()?->getId() ?? '',
            'width'               => $object->getWidth(),
            'height'              => $object->getHeight(),
            'length'              => $object->getLength(),
            'weight'              => $object->getWeight(),
            'packUnit'            => $object->getTranslation('packUnit'),
            'packUnitPlural'      => $object->getTranslation('packUnitPlural'),
            'referenceUnit'       => $object->getReferenceUnit(),
            'purchaseUnit'        => $object->getPurchaseUnit(),
            'manufacturerid'      => $object->getManufacturerId(),
            'manufacturer_title'  => $object->getManufacturer()?->getName(),
            'ratingAverage'       => $object->getRatingAverage(),
            'totalProductReviews' => $object->getProductReviews()->count(),
            'customFields'        => $this->processCustomFields($object->getCustomFields(), $salesChannelContext),
            'topseller'           => $object->getMarkAsTopseller(),
            'searchable'          => true,
            'searchkeys'          => $this->getSearchKeys($object),
            'tags'                => $object->getTags()?->map(fn (TagEntity $tag): string => $tag->getName()),
            'unit'                => $object->getUnit()?->getShortCode(),
            'price'               => $object->getCalculatedPrice()->getUnitPrice(),
            'referencePrice'      => $object->getCalculatedPrice()->getReferencePrice()?->getPrice(),
            'images'              => !empty($mediaUrls) ? $mediaUrls : [],
            'picture_url_main'    => $object->getCover() ? ('/' . $object->getCover()->getMedia()?->getPath()) : null,
            'url'                 => '/' . $this->getSeoUrlPath($object->getSeoUrls(), $salesChannelContext->getLanguageId()),
            'timestamp'           => ($object->getUpdatedAt() ?? $object->getCreatedAt())->format('Y-m-d H:i:s'),
        ];

        // Dispatch the ModifierQueryRequestEvent for products
        $event = new ModifierQueryRequestEvent($data);
        $this->eventDispatcher->dispatch($event, ModifierQueryRequestEvent::NAME_PRODUCT);

        // Return the potentially modified data
        return $event->getQuery()->getArrayCopy();
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

    private function getGroupedOptions(?PropertyGroupOptionCollection $properties, ?PropertyGroupOptionCollection $options): array
    {
        $grouped = [];

        foreach ($properties ?? [] as $property) {
            $group = $property->getGroup();
            if (!isset($grouped[$group->getId()])) {
                $grouped[$group->getId()] = [
                    'id'    => $group->getId(),
                    'title' => $group->getTranslation('name'),
                    'value' => [],
                ];
            }

            $grouped[$group->getId()]['value'][] = $property->getTranslation('name');
        }

        foreach ($options ?? [] as $option) {
            $group = $option->getGroup();
            if (!isset($grouped[$group->getId()])) {
                $grouped[$group->getId()] = [
                    'id'    => $group->getId(),
                    'title' => $group->getTranslation('name'),
                    'value' => [],
                ];
            }

            $grouped[$group->getId()]['value'][] = $option->getTranslation('name');
        }

        return array_values($grouped);
    }

    private function getSearchKeys(ProductEntity $product): ?string
    {
        $searchKeys = $product->getSearchKeywords()->map(fn (ProductSearchKeywordEntity $item): string => $item->getKeyword());
        if ($product->getTranslation('customSearchKeywords')) {
            $searchKeys[] = $product->getTranslation('customSearchKeywords');
        }

        return [] !== $searchKeys ? implode(' ', $searchKeys) : null;
    }
}

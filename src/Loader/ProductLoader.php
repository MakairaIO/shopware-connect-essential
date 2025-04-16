<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Loader;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductLoader
{
    public function __construct(protected SalesChannelRepository $productRepository)
    {
    }

    public function getAllIds(SalesChannelContext $salesChannelContext, ?bool $active = null): array
    {
        $criteria = new Criteria();
        if ($active !== null) {
            $criteria->addFilter(new EqualsFilter('active', $active));
        }

        return $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }

    public function loadByIds(array $ids, SalesChannelContext $salesChannelContext): EntityCollection
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociation('media.media');
        $criteria->addAssociation('configuratorSettings');
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('tags');
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('searchKeywords');
        $criteria->addAssociation('productReviews');
        $criteria->addAssociation('seoUrls');

        return $this->productRepository->search($criteria, $salesChannelContext);
    }
}

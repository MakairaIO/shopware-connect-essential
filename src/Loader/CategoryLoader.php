<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Loader;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CategoryLoader
{
    public function __construct(protected SalesChannelRepository $categoryRepository)
    {
    }

    public function getAllIds(SalesChannelContext $salesChannelContext, ?bool $active = null): array
    {
        $criteria = new Criteria();
        if ($active !== null) {
            $criteria->addFilter(new EqualsFilter('active', $active));
        }

        return $this->categoryRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }

    public function loadByIds(array $ids, SalesChannelContext $salesChannelContext): EntityCollection
    {
        $criteria = new Criteria($ids);
        $criteria->addAssociation('media');
        $criteria->addAssociation('seoUrls');

        return $this->categoryRepository->search($criteria, $salesChannelContext);
    }

    public function getSubcategories(string $parentId, SalesChannelContext $salesChannelContext): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', $parentId));

        return $this->categoryRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }
}

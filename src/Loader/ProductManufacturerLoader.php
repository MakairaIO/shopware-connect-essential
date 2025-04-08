<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Loader;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductManufacturerLoader
{
    public function __construct(protected EntityRepository $productManufacturerRepository)
    {
    }

    public function getAllIds(SalesChannelContext $salesChannelContext, ?bool $active = null): array
    {
        $criteria = new Criteria();
        if ($active !== null) {
            $criteria->addFilter(new EqualsFilter('active', $active));
        }

        return $this->productManufacturerRepository->searchIds($criteria, $salesChannelContext->getContext())->getIds();
    }

    public function loadByIds(array $ids, SalesChannelContext $salesChannelContext): EntityCollection
    {
        $criteria = new Criteria($ids);

        return $this->productManufacturerRepository->search($criteria, $salesChannelContext->getContext());
    }
}
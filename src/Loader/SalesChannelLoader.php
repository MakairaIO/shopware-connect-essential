<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Loader;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SalesChannelLoader
{
    public function __construct(protected EntityRepository $salesChannelRepository)
    {
    }

    public function getAllIds(Context $context, ?bool $active = null): array
    {
        $criteria = new Criteria();
        if ($active !== null) {
            $criteria->addFilter(new EqualsFilter('active', $active));
        }

        return $this->salesChannelRepository->searchIds($criteria, $context)->getIds();
    }

    public function getLanguages(Context $context, string $salesChannelId): LanguageCollection
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('languages');
        $criteria->addAssociation('languages.locale');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        return $salesChannel->getLanguages();
    }

    public function loadByIds(array $ids, Context $context): EntityCollection
    {
        $criteria = new Criteria($ids);

        return $this->salesChannelRepository->search($criteria, $context);
    }
}

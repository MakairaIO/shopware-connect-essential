<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Traits;

use Shopware\Core\Content\Seo\SeoUrl\SeoUrlCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait UrlTrait
{
    private function getSalesChannelUrl(SalesChannelContext $salesChannelContext): string
    {
        if ($salesChannelContext->getSalesChannel() === null) {
            return '';
        }

        if ($salesChannelContext->getSalesChannel()->getDomains()->count() === 0) {
            return '';
        }

        $languageDomain = $salesChannelContext->getSalesChannel()->getDomains()->filterByProperty('languageId', $salesChannelContext->getLanguageId())->first();
        if ($languageDomain === null) {
            return '';
        }

        return $languageDomain->getUrl();
    }

    private function getSeoUrlPath(SeoUrlCollection $seoUrls, string $languageId): string
    {
        if ($seoUrls->filterByProperty('isCanonical', true)->count() === 0) {
            return '';
        }

        return $seoUrls->filterByProperty('isCanonical', true)->first()->getSeoPathInfo();
    }
}
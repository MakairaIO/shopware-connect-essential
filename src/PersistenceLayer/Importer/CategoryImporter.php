<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Importer;

use MakairaConnectEssential\Loader\CategoryLoader;
use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Api\ApiConfig;
use MakairaConnectEssential\PersistenceLayer\Api\ApiGatewayFactory;
use MakairaConnectEssential\PersistenceLayer\Normalizer\CategoryNormalizer;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CategoryImporter
{
    public function __construct(
        protected ApiGatewayFactory $apiGatewayFactory,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected CategoryLoader $categoryLoader,
        protected CategoryNormalizer $categoryNormalizer,
        protected LoggerInterface $logger
    ) {
    }

    public function upsert(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $categoryIds = [], ?SymfonyStyle $io = null): void
    {
        if (empty($categoryIds)) {
            $categoryIds = $this->categoryLoader->getAllIds($salesChannelContext);
        }

        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages  = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            $io?->block(sprintf('Category for language: %s', $language->getLocale()->getCode()));

            $salesChannelLanguageContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelContext->getSalesChannelId(), [
                SalesChannelContextService::LANGUAGE_ID => $language->getId(),
            ]);

            try {
                $io?->progressStart(count($categoryIds));
                // TODO: add chunk size to plugin config
                foreach (array_chunk($categoryIds, 100) as $categoryChunk) {
                    $categories           = $this->categoryLoader->loadByIds($categoryChunk, $salesChannelLanguageContext);
                    $normalizedCategories = [];

                    foreach ($categories as $category) {
                        $io?->progressAdvance();
                        $normalizedCategories[] = [
                            'language' => substr($language->getLocale()->getCode(), 0, 2),
                            'data'     => $this->categoryNormalizer->normalize($category, null, ['salesChannelContext' => $salesChannelLanguageContext]),
                        ];
                    }
                    $apiGateway->insertPersistenceRevisions($normalizedCategories);
                }
                $io?->progressFinish();
            } catch (\Exception | HttpExceptionInterface | ExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                $this->logger->error('Error during category upsert', [
                    'message' => $exception->getMessage(),
                    'trace'   => $exception->getTraceAsString(),
                ]);
                $io?->error('An error occurred. Check the logs for more details.');
            }
        }
    }

    public function delete(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $categoryIds): void
    {
        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages  = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            try {
                foreach (array_chunk($categoryIds, 100) as $categoryChunk) {
                    $categoryData = array_map(
                        fn ($categoryId) => [
                            'type' => 'category',
                            'id'   => $categoryId,
                        ],
                        $categoryChunk
                    );
                    $apiGateway->deletePersistenceRevisions($categoryData, substr($language->getLocale()->getCode(), 0, 2));
                }
            } catch (\Exception | HttpExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                $this->logger->error('Error during category deletion', [
                    'message' => $exception->getMessage(),
                    'trace'   => $exception->getTraceAsString(),
                ]);
            }
        }
    }
}

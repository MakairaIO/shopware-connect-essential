<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Importer;

use MakairaConnectEssential\Loader\ProductLoader;
use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Api\ApiConfig;
use MakairaConnectEssential\PersistenceLayer\Api\ApiGatewayFactory;
use MakairaConnectEssential\PersistenceLayer\Normalizer\ProductNormalizer;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProductImporter
{
    public function __construct(
        protected ApiGatewayFactory $apiGatewayFactory,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected ProductLoader $productLoader,
        protected ProductNormalizer $productNormalizer,
    ) {
    }

    public function upsert(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $productIds = [], ?SymfonyStyle $io = null): void
    {
        if (empty($productIds)) {
            $productIds = $this->productLoader->getAllIds($salesChannelContext);
        }

        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            $io?->block(sprintf('Products for language: %s', $language->getLocale()->getCode()));

            $salesChannelLanguageContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelContext->getSalesChannelId(), [
                SalesChannelContextService::LANGUAGE_ID => $language->getId(),
            ]);

            try {
                $io?->progressStart(count($productIds));
                // TODO: add chunk size to plugin config
                foreach (array_chunk($productIds, 100) as $productChunk) {
                    $products = $this->productLoader->loadByIds($productChunk, $salesChannelLanguageContext);
                    $normalizedProducts = [];

                    foreach ($products as $product) {
                        $io?->progressAdvance();
                        $normalizedProducts[] = [
                            'language' => substr($language->getLocale()->getCode(), 0, 2),
                            'data' => $this->productNormalizer->normalize($product, null, ['salesChannelContext' => $salesChannelLanguageContext]),
                        ];
                    }
                    $apiGateway->insertPersistenceRevisions($normalizedProducts);
                }
                $io?->progressFinish();
            } catch (\Exception | HttpExceptionInterface | ExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                // TODO: Logging
                var_dump($exception->getMessage());
            }
        }
    }

    public function delete(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $productIds): void
    {
        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            try {
                foreach (array_chunk($productIds, 100) as $productChunk) {
                    $productData = array_map(fn($productId) => [
                        'type' => 'product',
                        'id' => $productId,
                    ], $productChunk
                    );
                    $apiGateway->deletePersistenceRevisions($productData, substr($language->getLocale()->getCode(), 0, 2));
                }
            } catch (\Exception | HttpExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                // TODO: Logging
                var_dump($exception->getMessage());
            }
        }
    }
}
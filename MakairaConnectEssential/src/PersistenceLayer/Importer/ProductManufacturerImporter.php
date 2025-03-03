<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Importer;

use MakairaConnectEssential\Loader\ProductManufacturerLoader;
use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Api\ApiConfig;
use MakairaConnectEssential\PersistenceLayer\Api\ApiGatewayFactory;
use MakairaConnectEssential\PersistenceLayer\Normalizer\ProductManufacturerNormalizer;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ProductManufacturerImporter
{
    public function __construct(
        protected ApiGatewayFactory $apiGatewayFactory,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
        protected ProductManufacturerLoader $productManufacturerLoader,
        protected ProductManufacturerNormalizer $productManufacturerNormalizer,
    ) {
    }

    public function upsert(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $productManufacturerIds = [], ?SymfonyStyle $io = null): void
    {
        if (empty($productManufacturerIds)) {
            $productManufacturerIds = $this->productManufacturerLoader->getAllIds($salesChannelContext);
        }

        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            $io?->block(sprintf('Productmanufacturer for language: %s', $language->getLocale()->getCode()));

            $salesChannelLanguageContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelContext->getSalesChannelId(), [
                SalesChannelContextService::LANGUAGE_ID => $language->getId(),
            ]);

            try {
                $io?->progressStart(count($productManufacturerIds));
                // TODO: add chunk size to plugin config
                foreach (array_chunk($productManufacturerIds, 100) as $productManufacturerChunk) {
                    $productManufacturers = $this->productManufacturerLoader->loadByIds($productManufacturerChunk, $salesChannelLanguageContext);
                    $normalizedProductManufacturer = [];
                    foreach ($productManufacturers as $productManufacturer) {
                        $io?->progressAdvance();
                        $normalizedProductManufacturer[] = [
                            'language' => substr($language->getLocale()->getCode(), 0, 2),
                            'data' => $this->productManufacturerNormalizer->normalize($productManufacturer, null, ['salesChannelContext' => $salesChannelLanguageContext]),
                        ];
                    }
                    $apiGateway->insertPersistenceRevisions($normalizedProductManufacturer);
                }
                $io?->progressFinish();
            } catch (\Exception | HttpExceptionInterface | ExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                // TODO: Logging
                var_dump($exception->getMessage());
            }
        }
    }

    public function delete(SalesChannelContext $salesChannelContext, ApiConfig $apiConfig, array $productManufacturerIds): void
    {
        $apiGateway = $this->apiGatewayFactory->create($apiConfig);
        $languages = $this->salesChannelLoader->getLanguages($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());

        foreach ($languages as $language) {
            try {
                foreach (array_chunk($productManufacturerIds, 100) as $productManufacturerChunk) {
                    $productManufacturerData = array_map(fn($productManufacturerId) => [
                        'type' => 'product',
                        'id' => $productManufacturerId,
                    ], $productManufacturerChunk
                    );
                    $apiGateway->deletePersistenceRevisions($productManufacturerData, substr($language->getLocale()->getCode(), 0, 2));
                }
            } catch (\Exception | HttpExceptionInterface | DecodingExceptionInterface | TransportExceptionInterface $exception) {
                // TODO: Logging
                var_dump($exception->getMessage());
            }
        }
    }
}
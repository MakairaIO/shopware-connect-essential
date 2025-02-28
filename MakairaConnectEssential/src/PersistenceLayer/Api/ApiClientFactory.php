<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Api;

use App\MakairaConnectEssential\src\PersistenceLayer\Api\ApiClient;
use App\MakairaConnectEssential\src\MakairaConnectEssential;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ApiClientFactory
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected string $shopwareVersion,
    ) {
    }

    public function create(ApiConfig $apiConfig): ApiClient
    {
        $userAgent = sprintf('Shopware/%s MakairaConnect/%s', $this->shopwareVersion, MakairaConnectEssential::PLUGIN_VERSION);

        return new ApiClient(
            $this->httpClient,
            new RequestSigner($apiConfig->getSharedSecret()),
            $apiConfig->getBaseUrl(),
            $apiConfig->getInstance(),
            $userAgent,
            $apiConfig->getTimeout(),
        );
    }
}

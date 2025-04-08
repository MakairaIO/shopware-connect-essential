<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Api;

use Psr\Clock\ClockInterface;

final readonly class ApiGatewayFactory
{
    public function __construct(
        protected ApiClientFactory $apiClientFactory,
        protected ClockInterface $clock,
    ) {
    }

    public function create(ApiConfig $apiConfig): ApiGateway
    {
        return new ApiGateway(
            $this->apiClientFactory->create($apiConfig),
            $this->clock,
            $apiConfig->getCustomer(),
            $apiConfig->getInstance(),
        );
    }
}

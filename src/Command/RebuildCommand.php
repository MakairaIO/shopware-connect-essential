<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Command;

use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Api\ApiGatewayFactory;
use MakairaConnectEssential\Utils\PluginConfig;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'makaira:persistence-layer:rebuild',
    description: 'Initialize rebuild of the Makaira persistence layer',
)]
class RebuildCommand extends Command
{
    public function __construct(
        protected PluginConfig $pluginConfig,
        protected ApiGatewayFactory $apiGatewayFactory,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('salesChannelId', InputArgument::OPTIONAL, 'Sales channel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $context = Context::createCLIContext();

        $salesChannelIds = $input->getArgument('salesChannelId') ? [$input->getArgument('salesChannelId')] : null;

        if (empty($salesChannelIds)) {
            $salesChannelIds = $this->salesChannelLoader->getAllIds($context, true);
        }

        foreach ($salesChannelIds as $salesChannelId) {
            if (!$this->pluginConfig->hasValidMakairaCredentials($salesChannelId)) {
                continue;
            }

            $salesChannelContext = $this->salesChannelContextFactory->create(Uuid::randomHex(), $salesChannelId);
            $io->title('Initialize rebuild for sales-channel "' . $salesChannelContext->getSalesChannel()->getName() . '"');

            try {
                $apiConfig  = $this->pluginConfig->createMakairaApiConfig($salesChannelId);
                $apiGateway = $this->apiGatewayFactory->create($apiConfig);
                $apiGateway->rebuildPersistenceLayer();
                $io->success('Finished');
            } catch (\Exception | ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
                $io->error($exception->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}

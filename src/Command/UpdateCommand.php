<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Command;

use MakairaConnectEssential\Loader\SalesChannelLoader;
use MakairaConnectEssential\PersistenceLayer\Importer\CategoryImporter;
use MakairaConnectEssential\PersistenceLayer\Importer\ProductImporter;
use MakairaConnectEssential\PersistenceLayer\Importer\ProductManufacturerImporter;
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

#[AsCommand(
    name: 'makaira:persistence-layer:update',
    description: 'Update the Makaira persistence layer',
)]
class UpdateCommand extends Command
{
    public function __construct(
        protected CategoryImporter $categoryImporter,
        protected ProductManufacturerImporter $productManufacturerImporter,
        protected ProductImporter $productImporter,
        protected PluginConfig $pluginConfig,
        protected SalesChannelLoader $salesChannelLoader,
        protected AbstractSalesChannelContextFactory $salesChannelContextFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Start a new full import.');
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
            $io->title(sprintf('Updata the Makaira persistence layer for sales-channel %s', $salesChannelContext->getSalesChannel()->getName()));

            try {
                $makairaApiConfig = $this->pluginConfig->createMakairaApiConfig($salesChannelId);

                $this->categoryImporter->upsert($salesChannelContext, $makairaApiConfig, [], $io);
                $this->productManufacturerImporter->upsert($salesChannelContext, $makairaApiConfig, [], $io);
                $this->productImporter->upsert($salesChannelContext, $makairaApiConfig, [], $io);
                $io->success('Finished');
            } catch (\Exception $exception) {
                $io->error($exception->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}

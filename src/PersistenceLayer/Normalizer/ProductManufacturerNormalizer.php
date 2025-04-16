<?php

declare(strict_types=1);

namespace MakairaConnectEssential\PersistenceLayer\Normalizer;

use MakairaConnectEssential\Events\ModifierQueryRequestEvent;
use MakairaConnectEssential\PersistenceLayer\Traits\CustomFieldsTrait;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProductManufacturerNormalizer implements NormalizerInterface
{
    use CustomFieldsTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (! $object instanceof ProductManufacturerEntity) {
            return null;
        }

        $data = [
            'id'                 => $object->getId(),
            'type'               => 'manufacturer',
            'manufacturer_title' => $object->getTranslation('name'),
            'customFields'       => $this->processCustomFields($object->getCustomFields()),
            'active'             => true,
            'timestamp'          => ($object->getUpdatedAt() ?? $object->getCreatedAt())->format('Y-m-d H:i:s'),
        ];

        // Dispatch the ModifierQueryRequestEvent for manufacturers
        $event = new ModifierQueryRequestEvent($data);
        $this->eventDispatcher->dispatch($event, ModifierQueryRequestEvent::NAME_MANUFACTURER);

        // Return the potentially modified data
        return $event->getQuery()->getArrayCopy();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ProductManufacturerEntity && $format === 'json';
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ProductManufacturerEntity::class => true,
        ];
    }
}

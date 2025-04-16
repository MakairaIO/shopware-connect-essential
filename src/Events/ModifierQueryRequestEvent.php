<?php

declare(strict_types=1);

namespace MakairaConnectEssential\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Possibility to change the query before sending it to Makaira.
 */
class ModifierQueryRequestEvent extends Event
{
    public const NAME_CATEGORY = 'makaira.essential.request.modifier.category';

    public const NAME_PRODUCT = 'makaira.essential.request.modifier.product';

    public const NAME_VARIANT = 'makaira.essential.request.modifier.variant';

    public const NAME_MANUFACTURER = 'makaira.essential.request.modifier.manufacturer';


    private \ArrayObject $query;

    public function __construct(
        array $query,
    ) {
        $this->query = new \ArrayObject($query);
    }

    public function getQuery(): \ArrayObject
    {
        return $this->query;
    }
}

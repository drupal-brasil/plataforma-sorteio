<?php

declare(strict_types=1);

namespace Drupal\db_prize;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a prize entity type.
 */
interface PrizeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

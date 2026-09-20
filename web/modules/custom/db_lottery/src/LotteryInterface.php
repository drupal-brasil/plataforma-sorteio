<?php

declare(strict_types=1);

namespace Drupal\db_lottery;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a lottery entity type.
 */
interface LotteryInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

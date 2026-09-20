<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a lottery subscription entity type.
 */
interface LotterySubscriptionInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

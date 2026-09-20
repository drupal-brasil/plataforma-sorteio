<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage handler for lottery subscription entities.
 */
final class LotterySubscriptionStorage extends SqlContentEntityStorage {

  /**
   * Loads the current active subscription for a user in a lottery.
   */
  public function loadCurrentSubscriptionForUser(int $lottery_id, int $user_id) {
    $query = $this->getQuery()
      ->condition('status', 1)
      ->condition('lottery', $lottery_id)
      ->condition('uid', $user_id)
      ->range(0, 1)
      ->accessCheck(FALSE);

    $ids = $query->execute();

    return $ids ? $this->load(reset($ids)) : NULL;
  }

  /**
   * Counts active subscriptions for a lottery.
   */
  public function countActiveByLotteryId(int $lottery_id): int {
    $query = $this->getQuery()
      ->count()
      ->condition('status', 1)
      ->condition('lottery', $lottery_id)
      ->accessCheck(FALSE);

    return (int) $query->execute();
  }

  /**
   * Loads a random active subscription for a lottery.
   */
  public function loadRandomActiveByLotteryId(int $lottery_id) {
    $query = $this->getQuery()
      ->condition('status', 1)
      ->condition('lottery', $lottery_id)
      ->accessCheck(FALSE);

    $ids = array_values($query->execute());
    if ($ids === []) {
      return NULL;
    }

    $random_index = random_int(0, count($ids) - 1);
    return $this->load($ids[$random_index]);
  }
}

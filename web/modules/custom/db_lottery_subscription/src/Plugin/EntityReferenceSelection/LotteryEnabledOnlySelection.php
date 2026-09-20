<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Lottery Enabled Only Plugin.
 */
#[EntityReferenceSelection(
  id: 'lottery_enabled_only_selection',
  label: new TranslatableMarkup('Lista somente os sorteios ativos'),
  group: 'lottery_enabled_only_selection',
  weight: 1,
  entity_types: ['lottery'],
)]
final class LotteryEnabledOnlySelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS'): QueryInterface {
    $query = parent::buildEntityQuery($match, $match_operator);

    $query->condition('status', 1);

    return $query;
  }

}

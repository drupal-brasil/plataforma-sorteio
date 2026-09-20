<?php

declare(strict_types=1);

namespace Drupal\db_lottery;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the lottery entity type.
 */
final class LotteryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['prize'] = $this->t('Prêmio');
    $header['winner'] = $this->t('Vencedor');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\db_lottery\LotteryInterface $entity */
    $prize = $entity->get('prize')->entity;
    $winner = $entity->get('winner')->entity;

    $row['id'] = $entity->id();
    $row['title'] = $entity->get('title')->value;
    $row['prize'] = $prize?->label() ?? $this->t('None');
    $row['winner'] = $winner?->label() ?? $winner?->getEmail() ?? $this->t('None');
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
//    $username_options = [
//      'label' => 'hidden',
//      'settings' => ['link' => $entity->get('uid')->entity->isAuthenticated()],
//    ];
//    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Lottery Subscription form.
 */
final class LotteryUserSubscription extends FormBase {

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountInterface $currentUser;

    public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('current_user')
        );
    }

    /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'db_lottery_subscription_user_subscription';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
      $lottery_storage = $this->entityTypeManager->getStorage('lottery');

      /** @var \Drupal\db_lottery\Entity\Lottery|null $lottery */
      $lottery = $lottery_storage->loadLatestActive();

      if (! $lottery) {
          $form['no_lottery'] = [
              '#markup' => $this->t('No momento não há nenhum sorteio acontecendo!'),
          ];

          return $form;
      }

      $form['intro'] = [
          '#markup' => $this->t('Sua inscrição será para: <strong>@title</strong>. O período de incrição será de <strong>@start_date</strong> até <strong>@end_date</strong>. Faça sua inscrição pelo botão <strong>Inscrever-se</strong>.', [
              '@title' => $lottery->get('title')->value,
              '@start_date' => $lottery->get('startend_date')->start_date->format('d/m/Y'),
              '@end_date' => $lottery->get('startend_date')->end_date->format('d/m/Y'),
          ]),
      ];

      $form_state->set('lottery_id', $lottery->id());

      $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Inscrever-se'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $lottery_id = (int) $form_state->get('lottery_id');
    $uid = (int) $this->currentUser->id();

    $lottery_subscription_storage = $this->entityTypeManager->getStorage('lottery_subscription');

    /** @var \Drupal\db_lottery_subscription\Entity\LotterySubscription|null $lottery_subscription */
    $lottery_subscription = $lottery_subscription_storage->loadCurrentSubscriptionForUser($lottery_id, $uid);

    if ($lottery_subscription) {
      $this->messenger()->addWarning($this->t('Você já está participando desse sorteio!'));

      return;
    }

    $new_subscription = $lottery_subscription_storage->create([
     'title' => 'Inscrição de ' . $this->currentUser->getEmail(),
      'uid' => $uid,
      'lottery' => $lottery_id,
    ]);

    try {
        $new_subscription->save();

        $this->messenger()->addStatus($this->t('Inscrição realizada com sucesso!'));
    }
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Você já está participando desse sorteio!'));
    }
  }

}

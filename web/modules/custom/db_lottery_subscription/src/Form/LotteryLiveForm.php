<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the live lottery draw form.
 */
final class LotteryLiveForm extends FormBase {

  /**
   * Creates the form instance.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'db_lottery_subscription_live';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'db_lottery_subscription/live_draw';

    $subscription_storage = $this->entityTypeManager->getStorage('lottery_subscription');
    $winner_data = $form_state->get('winner_data');

    /** @var \Drupal\db_lottery\Entity\Lottery|null $lottery */
    $lottery = $this->loadDisplayedLottery($form_state);

    $form['live_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'lottery-live-wrapper',
        'class' => ['lottery-live'],
      ],
    ];

    if (! $lottery) {
      $form['live_wrapper']['empty'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['lottery-live__empty']],
        'title' => [
          '#markup' => '<h2 class="lottery-live__empty-title">' . $this->t('Nenhum sorteio ativo no momento') . '</h2>',
        ],
        'description' => [
          '#markup' => '<p class="lottery-live__empty-text">' . $this->t('Ative um sorteio para liberar a transmissão ao vivo e o botão de sorteio.') . '</p>',
        ],
      ];

      return $form;
    }

    $lottery_id = (int) $lottery->id();
    $total_subscriptions = $subscription_storage->countActiveByLotteryId($lottery_id);
    $current_winner = $lottery->get('winner')->entity;
    $is_draw_completed = $winner_data !== NULL || $current_winner instanceof UserInterface;

    $form['lottery_id'] = [
      '#type' => 'hidden',
      '#value' => $lottery_id,
    ];

    $form['live_wrapper']['hero'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['lottery-live__hero']],
      'eyebrow' => [
        '#markup' => '<div class="lottery-live__eyebrow">' . $this->t('Transmissão ao vivo') . '</div>',
      ],
      'title' => [
        '#markup' => '<h2 class="lottery-live__title">' . Html::escape($lottery->label()) . '</h2>',
      ],
      'description' => [
        '#markup' => '<p class="lottery-live__description">' . $this->t('Tudo pronto para revelar o vencedor. Quando clicar em <strong>Sortear</strong>, o sistema escolherá um inscrito ativo da campanha em andamento.') . '</p>',
      ],
    ];

    $form['live_wrapper']['stats'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['lottery-live__stats']],
      'subscribers' => $this->buildStatCard(
        $this->t('Inscritos ativos'),
        (string) $total_subscriptions,
        $this->t('participantes válidos para o sorteio'),
        'lottery-live__stat--accent',
      ),
      'period' => $this->buildStatCard(
        $this->t('Período'),
        $this->formatDateRange($lottery),
        $this->t('janela oficial do sorteio'),
        'lottery-live__stat--sun',
      ),
      'prize' => $this->buildStatCard(
        $this->t('Prêmio'),
        $lottery->get('prize')->entity?->label() ?? (string) $this->t('Sem prêmio definido'),
        $this->t('recompensa vinculada ao sorteio'),
        'lottery-live__stat--mint',
      ),
    ];

    $form['live_wrapper']['result'] = $this->buildResultSection($lottery, $winner_data);

    $form['live_wrapper']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['lottery-live__actions']],
      'submit' => [
        '#type' => 'submit',
        '#value' => $is_draw_completed ? $this->t('Sorteio concluído') : ($total_subscriptions > 0 ? $this->t('Sortear') : $this->t('Aguardando inscritos')),
        '#button_type' => 'primary',
        '#ajax' => [
          'callback' => '::refreshLiveWrapper',
          'wrapper' => 'lottery-live-wrapper',
        ],
        '#attributes' => [
          'class' => ['lottery-live__submit'],
          'data-lottery-live-submit' => 'true',
        ],
        '#disabled' => $total_subscriptions === 0 || $is_draw_completed,
      ],
    ];

    return $form;
  }

  /**
   * Ajax callback for live wrapper refresh.
   */
  public function refreshLiveWrapper(array &$form, FormStateInterface $form_state): array {
    return $form['live_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $lottery_id = (int) $form_state->getValue('lottery_id');
    $lottery_storage = $this->entityTypeManager->getStorage('lottery');
    $subscription_storage = $this->entityTypeManager->getStorage('lottery_subscription');

    /** @var \Drupal\db_lottery\Entity\Lottery|null $lottery */
    $lottery = $lottery_storage->load($lottery_id);

    if (!$lottery) {
      $this->messenger()->addWarning($this->t('O sorteio ativo não está mais disponível.'));
      $form_state->setRebuild();
      return;
    }

    if ($lottery->get('winner')->entity instanceof UserInterface || !(bool) $lottery->get('status')->value) {
      $this->messenger()->addWarning($this->t('Este sorteio já foi encerrado e não pode ser sorteado novamente.'));
      $form_state->setRebuild();
      return;
    }

    if ($subscription_storage->countActiveByLotteryId($lottery_id) === 0) {
      $this->messenger()->addWarning($this->t('Ainda não há inscritos suficientes para realizar o sorteio.'));
      $form_state->setRebuild();
      return;
    }

    /** @var \Drupal\db_lottery_subscription\Entity\LotterySubscription|null $winner_subscription */
    $winner_subscription = $subscription_storage->loadRandomActiveByLotteryId($lottery_id);
    if (!$winner_subscription) {
      $this->messenger()->addError($this->t('Não foi possível selecionar um inscrito para o sorteio.'));
      $form_state->setRebuild();
      return;
    }

    /** @var \Drupal\user\UserInterface|null $winner */
    $winner = $winner_subscription->get('uid')->entity;
    if (!$winner instanceof UserInterface) {
      $this->messenger()->addError($this->t('O vencedor sorteado não possui usuário válido.'));
      $form_state->setRebuild();
      return;
    }

    $lottery->set('winner', $winner->id());
    $lottery->set('status', FALSE);
    $lottery->save();

    $winner_name = $winner->getDisplayName() ?: $winner->getEmail();
    $form_state->set('winner_data', [
      'celebration_id' => (string) round(microtime(TRUE) * 1000),
      'lottery_id' => (string) $lottery->id(),
      'name' => $winner_name,
      'email' => $winner->getEmail(),
      'subscription_id' => (string) $winner_subscription->id(),
      'lottery_title' => $lottery->label(),
      'prize_title' => $lottery->get('prize')->entity?->label() ?? (string) $this->t('Prêmio surpresa'),
    ]);
    $form_state->setRebuild();
  }

  /**
   * Builds a stat card.
   */
  private function buildStatCard(string|\Stringable $label, string|\Stringable $value, string|\Stringable $meta, string $modifier): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['lottery-live__stat', $modifier],
      ],
      'label' => [
        '#markup' => '<div class="lottery-live__stat-label">' . Html::escape((string) $label) . '</div>',
      ],
      'value' => [
        '#markup' => '<div class="lottery-live__stat-value">' . Html::escape((string) $value) . '</div>',
      ],
      'meta' => [
        '#markup' => '<div class="lottery-live__stat-meta">' . Html::escape((string) $meta) . '</div>',
      ],
    ];
  }

  /**
   * Builds the result section.
   */
  private function buildResultSection(object $lottery, ?array $winner_data): array {
    $attributes = ['class' => ['lottery-live__result']];

    if ($winner_data) {
      $attributes['class'][] = 'lottery-live__result--winner';
      $attributes['data-lottery-live-celebration-id'] = $winner_data['celebration_id'];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => $attributes,
    ];

    if ($winner_data) {
      $build['badge'] = [
        '#markup' => '<div class="lottery-live__winner-badge">' . $this->t('Parabéns, temos um vencedor!') . '</div>',
      ];
      $build['title'] = [
        '#markup' => '<h3 class="lottery-live__winner-title">' . Html::escape($winner_data['name']) . '</h3>',
      ];
      $build['subtitle'] = [
        '#markup' => '<p class="lottery-live__winner-subtitle">' . $this->t('Venceu o sorteio <strong>@lottery</strong> e vai receber <strong>@prize</strong>.', [
          '@lottery' => $winner_data['lottery_title'],
          '@prize' => $winner_data['prize_title'],
        ]) . '</p>',
      ];
      $build['meta'] = [
        '#markup' => '<div class="lottery-live__winner-meta">' . $this->t('Contato: @email | Inscrição #@subscription', [
          '@email' => $winner_data['email'],
          '@subscription' => $winner_data['subscription_id'],
        ]) . '</div>',
      ];
      return $build;
    }

    $current_winner = $lottery->get('winner')->entity;
    if ($current_winner instanceof UserInterface) {
      $winner_name = $current_winner->getDisplayName() ?: $current_winner->getEmail();
      $build['badge'] = [
        '#markup' => '<div class="lottery-live__winner-badge lottery-live__winner-badge--muted">' . $this->t('Sorteio encerrado') . '</div>',
      ];
      $build['title'] = [
        '#markup' => '<h3 class="lottery-live__winner-title">' . Html::escape($winner_name) . '</h3>',
      ];
      $build['subtitle'] = [
        '#markup' => '<p class="lottery-live__winner-subtitle">' . $this->t('Este sorteio já possui um vencedor definido e foi removido das listagens ativas.') . '</p>',
      ];
      return $build;
    }

    $build['placeholder'] = [
      '#markup' => '<div class="lottery-live__result-placeholder">' . $this->t('Clique em <strong>Sortear</strong> para revelar o vencedor desta rodada.') . '</div>',
    ];

    return $build;
  }

  /**
   * Formats the lottery date range.
   */
  private function formatDateRange(object $lottery): string {
    $start = $lottery->get('startend_date')->start_date?->getTimestamp();
    $end = $lottery->get('startend_date')->end_date?->getTimestamp();

    if (!$start || !$end) {
      return (string) $this->t('Período não informado');
    }

    return $this->dateFormatter->format($start, 'custom', 'd/m/Y H:i') . ' - ' . $this->dateFormatter->format($end, 'custom', 'd/m/Y H:i');
  }

  /**
   * Loads the lottery that should be displayed on the live screen.
   */
  private function loadDisplayedLottery(FormStateInterface $form_state): ?object {
    $lottery_storage = $this->entityTypeManager->getStorage('lottery');
    $winner_data = $form_state->get('winner_data');

    if (is_array($winner_data) && isset($winner_data['lottery_id'])) {
      /** @var \Drupal\db_lottery\Entity\Lottery|null $lottery */
      $lottery = $lottery_storage->load((int) $winner_data['lottery_id']);
      if ($lottery) {
        return $lottery;
      }
    }

    /** @var \Drupal\db_lottery\Entity\Lottery|null $lottery */
    $lottery = $lottery_storage->loadLatestActive();
    return $lottery;
  }

}

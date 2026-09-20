<?php

declare(strict_types=1);

namespace Drupal\db_lottery_subscription\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\db_lottery_subscription\Form\LotterySubscriptionForm;
use Drupal\db_lottery_subscription\LotterySubscriptionAccessControlHandler;
use Drupal\db_lottery_subscription\LotterySubscriptionInterface;
use Drupal\db_lottery_subscription\LotterySubscriptionListBuilder;
use Drupal\db_lottery_subscription\LotterySubscriptionStorage;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the lottery subscription entity class.
 */
#[ContentEntityType(
  id: 'lottery_subscription',
  label: new TranslatableMarkup('Inscrição Sorteio'),
  label_collection: new TranslatableMarkup('Inscrições de Sorteios'),
  label_singular: new TranslatableMarkup('inscrição sorteio'),
  label_plural: new TranslatableMarkup('inscrições sorteios'),
  entity_keys: [
    'id' => 'id',
    'label' => 'title',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'storage' => LotterySubscriptionStorage::class,
    'list_builder' => LotterySubscriptionListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => LotterySubscriptionAccessControlHandler::class,
    'form' => [
      'add' => LotterySubscriptionForm::class,
      'edit' => LotterySubscriptionForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/lottery-subscription',
    'add-form' => '/lottery-subscription/add',
    'canonical' => '/lottery-subscription/{lottery_subscription}',
    'edit-form' => '/lottery-subscription/{lottery_subscription}/edit',
    'delete-form' => '/lottery-subscription/{lottery_subscription}/delete',
    'delete-multiple-form' => '/admin/content/lottery-subscription/delete-multiple',
    'register-form' => '/admin/content/lottery-subscription/register',
  ],
  admin_permission: 'administer lottery_subscription',
  base_table: 'lottery_subscription',
  label_count: [
    'singular' => '@count inscrição sorteio',
    'plural' => '@count inscrições sorteios',
  ],
  field_ui_base_route: 'entity.lottery_subscription.settings',
)]
class LotterySubscription extends ContentEntityBase implements LotterySubscriptionInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if (! $this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    if ($this->get('title')->isEmpty()) {
        $this->set('title', 'Incrição de ' . $this->get('uid')->entity->get('mail')->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
          'region' => 'hidden',
          'type' => 'hidden',
          'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
          'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
              'match_operator' => 'CONTAINS',
              'size' => 25,
              'placeholder' => '',
          ],
          'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['lottery'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sorteio'))
      ->setSetting('target_type', 'lottery')
      ->setSetting('handler', 'lottery_enabled_only_selection')
      ->setDisplayOptions('form', [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
              'match_operator' => 'CONTAINS',
              'size' => 25,
              'placeholder' => '',
          ],
          'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
          'label' => 'above',
          'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the lottery subscription was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the lottery subscription was last edited.'));

    return $fields;
  }

}

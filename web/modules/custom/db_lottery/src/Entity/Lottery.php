<?php

declare(strict_types=1);

namespace Drupal\db_lottery\Entity;

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
use Drupal\db_lottery\Form\LotteryForm;
use Drupal\db_lottery\LotteryAccessControlHandler;
use Drupal\db_lottery\LotteryInterface;
use Drupal\db_lottery\LotteryListBuilder;
use Drupal\db_lottery\LotteryStorage;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the lottery entity class.
 */
#[ContentEntityType(
  id: 'lottery',
  label: new TranslatableMarkup('Sorteio'),
  label_collection: new TranslatableMarkup('Sorteios'),
  label_singular: new TranslatableMarkup('sorteio'),
  label_plural: new TranslatableMarkup('sorteios'),
  entity_keys: [
    'id' => 'id',
    'label' => 'title',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'storage' => LotteryStorage::class,
    'list_builder' => LotteryListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => LotteryAccessControlHandler::class,
    'form' => [
      'add' => LotteryForm::class,
      'edit' => LotteryForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/lottery',
    'add-form' => '/lottery/add',
    'canonical' => '/lottery/{lottery}',
    'edit-form' => '/lottery/{lottery}/edit',
    'delete-form' => '/lottery/{lottery}/delete',
    'delete-multiple-form' => '/admin/content/lottery/delete-multiple',
  ],
  admin_permission: 'administer lottery',
  base_table: 'lottery',
  label_count: [
    'singular' => '@count loterias',
    'plural' => '@count loterias',
  ],
  field_ui_base_route: 'entity.lottery.settings',
)]
class Lottery extends ContentEntityBase implements LotteryInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

      $fields['description'] = BaseFieldDefinition::create('text_long')
          ->setLabel(t('Description'))
          ->setDisplayOptions('form', [
              'type' => 'text_textarea',
              'weight' => -4,
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayOptions('view', [
              'type' => 'text_default',
              'label' => 'above',
              'weight' => -4,
          ])
          ->setDisplayConfigurable('view', TRUE);

      $fields['startend_date'] = BaseFieldDefinition::create('daterange')
        ->setLabel(t('Janela para o sorteio'))
        ->setDescription(t('A data de ínicio e fim para o sorteio'))
        ->setRequired(TRUE)
        ->setRevisionable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'daterange_default',
            'weight' => -3,
            'settings' => [
                'datetime_type' => 'datetime',
            ],
        ])
        ->setDisplayOptions('view', [
            'type' => 'daterange_default',
            'weight' => -3,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 61,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 61,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

      $fields['prize'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Prêmio'))
          ->setSetting('target_type', 'prize')
          ->setDisplayOptions('form', [
              'type' => 'entity_reference_autocomplete',
              'settings' => [
                  'match_operator' => 'CONTAINS',
                  'size' => 60,
                  'placeholder' => '',
              ],
              'weight' => 15,
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayOptions('view', [
              'label' => 'above',
              'weight' => 15,
          ])
          ->setDisplayConfigurable('view', TRUE);

      $fields['winner'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Vencedor'))
          ->setSetting('target_type', 'user')
          ->setDisplayOptions('form', [
              'type' => 'entity_reference_autocomplete',
              'settings' => [
                  'match_operator' => 'CONTAINS',
                  'size' => 60,
                  'placeholder' => '',
              ],
              'weight' => 15,
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayOptions('view', [
              'label' => 'above',
              'type' => 'author',
              'weight' => 15,
          ])
          ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the lottery was created.'))
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
      ->setDescription(t('The time that the lottery was last edited.'));

    return $fields;
  }

  public function getEndDate() {
	  return $this->get('startend_date')->end_date;
  }

}

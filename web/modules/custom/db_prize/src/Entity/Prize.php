<?php

declare(strict_types=1);

namespace Drupal\db_prize\Entity;

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
use Drupal\db_prize\Form\PrizeForm;
use Drupal\db_prize\PrizeAccessControlHandler;
use Drupal\db_prize\PrizeInterface;
use Drupal\db_prize\PrizeListBuilder;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the prize entity class.
 */
#[ContentEntityType(
  id: 'prize',
  label: new TranslatableMarkup('Prêmio'),
  label_collection: new TranslatableMarkup('Prêmios'),
  label_singular: new TranslatableMarkup('prêmio'),
  label_plural: new TranslatableMarkup('prêmios'),
  entity_keys: [
    'id' => 'id',
    'label' => 'title',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => PrizeListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => PrizeAccessControlHandler::class,
    'form' => [
      'add' => PrizeForm::class,
      'edit' => PrizeForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/prize',
    'add-form' => '/prize/add',
    'canonical' => '/prize/{prize}',
    'edit-form' => '/prize/{prize}/edit',
    'delete-form' => '/prize/{prize}/delete',
    'delete-multiple-form' => '/admin/content/prize/delete-multiple',
  ],
  admin_permission: 'administer prize',
  base_table: 'prize',
  label_count: [
    'singular' => '@count prêmios',
    'plural' => '@count prêmios',
  ],
  field_ui_base_route: 'entity.prize.settings',
)]
class Prize extends ContentEntityBase implements PrizeInterface {

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

    $fields['promo_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Promocional'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

      $fields['promo_pdf'] = BaseFieldDefinition::create('file')
          ->setLabel(t('Arquivo PDF do Código Promocional'))
          ->setDescription(t('Faça upload do arquivo PDF que contém o código promocional.'))
          ->setRequired(FALSE)
          ->setSettings([
              'file_directory' => 'prizes/pdf',
              'file_extensions' => 'pdf',
              'max_filesize' => '10 MB',
              'uri_scheme' => 'public',  // ou "private"
          ])
          ->setDisplayOptions('form', [
              'type' => 'file_generic',
              'weight' => -1,
              'settings' => [
                  'progress_indicator' => 'throbber',
              ],
          ])
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayOptions('view', [
              'label' => 'hidden',
              'type' => 'file_default',
              'weight' => -1,
          ])
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
      ->setDescription(t('The time that the prize was created.'))
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
      ->setDescription(t('The time that the prize was last edited.'));

    return $fields;
  }

}

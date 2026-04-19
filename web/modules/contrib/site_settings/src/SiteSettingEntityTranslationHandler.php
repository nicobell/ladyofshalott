<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for eck entities.
 */
class SiteSettingEntityTranslationHandler extends ContentTranslationHandler {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation'])) {
      // We do not need to show these values on site setting forms: they inherit
      // the basic property values.
      $form['content_translation']['status']['#access'] = FALSE;
      $form['content_translation']['name']['#access'] = FALSE;
      $form['content_translation']['created']['#access'] = FALSE;
    }

    // Change the submit button labels if there was a status field they affect
    // in which case their publishing / unpublishing may or may not apply
    // to all translations.
    $formObject = $form_state->getFormObject();
    $formLangcode = $formObject->getFormLangcode($form_state);
    $translations = $entity->getTranslationLanguages();

    if (!$entity->isNew() && (!isset($translations[$formLangcode]) || count($translations) > 1) && $entity->hasField('status') && isset($form['actions']['submit'])) {
      $status_translatable = $entity->getFieldDefinition('status')->isTranslatable();
      $form['actions']['submit']['#value'] .= ' ' . ($status_translatable ? $this->t('(this translation)') : $this->t('(all translations)'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return $this->t('<em>Edit @type</em> @title', [
      '@type' => $entity->get('type')->entity?->label(),
      '@title' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue('content_translation')) {
      $translation = &$form_state->getValue('content_translation');
      $translation['status'] = $entity->isPublished();
      $account = $entity->get('user_id')->entity;
      $translation['user_id'] = $account ? $account->id() : 0;
      $translation['created'] = $this->dateFormatter->format($entity->get('created')->value, 'custom', 'Y-m-d H:i:s O');
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}

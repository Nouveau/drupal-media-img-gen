<?php

namespace Drupal\ai_image_gen\Form;

use Drupal\ai\Enum\AiModelCapability;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AI Image Generator module.
 */
class AiImageGenSettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_image_gen.settings';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * AI Provider service.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->providerManager = $container->get('ai.provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_translate_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load config.
    $config = $this->config(static::CONFIG_NAME);

    $form['prompt'] = [
      '#title' => $this->t('Image generation prompt'),
      '#type' => 'textarea',
      '#default_value' => $config->get('prompt') ?? '',
      '#description' => $this->t('Prompt used for generating the image.'),
      '#required' => TRUE,
    ];
    $form['longer_description'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Prompt is rendered using Twig rendering engine and supports the following tokens:'),
        '{{ entity_lang_name }} - ' . $this->t('Human readable name of the entity language'),
      ],
    ];

    // Make a select with all the image styles availabe.
    $imageStyles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $imageStylesOptions = [];
    foreach ($imageStyles as $imageStyle) {
      $imageStylesOptions[$imageStyle->id()] = $imageStyle->label();
    }
    $form['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#options' => $imageStylesOptions,
      '#default_value' => $config->get('image_style') ?? 'ai_image_gen',
      '#empty_option' => $this->t('Original'),
    ];

    $models = $this->providerManager->getSimpleProviderModelOptions('text_to_image', TRUE, TRUE);

    $form['ai_model'] = [
      '#title' => $this->t('AI provider/model'),
      '#type' => 'select',
      '#options' => $models,
      '#default_value' => $config->get('ai_model') ?? '',
      '#empty_option' => $this->t('Use Default Text To Image Model'),
      '#description' => $this->t('AI model to use for generating the image.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::CONFIG_NAME)
      ->set('prompt', $form_state->getValue('prompt'))
      ->set('image_style', $form_state->getValue('image_style'))
      ->set('ai_model', $form_state->getValue('ai_model'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

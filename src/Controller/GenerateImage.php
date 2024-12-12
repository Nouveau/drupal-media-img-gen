<?php

namespace Drupal\ai_image_gen\Controller;

use Drupal\ai\AiProviderPluginManager;
//use Drupal\ai\OperationType\Chat\ChatInput;
//use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\TextToImage\TextToImageInput;
use Drupal\ai\OperationType\GenericType\ImageFile;
use Drupal\ai_image_gen\ProviderHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines an AI Translate Controller.
 */
class GenerateImage extends ControllerBase {

  /**
   * AI module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $aiConfig;

  /**
   * AI image gen configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $genConfig;

  /**
   * AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected AiProviderPluginManager $aiProviderManager;

  /**
   * Twig engine.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected TwigEnvironment $twig;

  /**
   * The AI provider helper.
   *
   * @var \Drupal\ai_image_gen\ProviderHelper
   */
  protected ProviderHelper $providerHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    $instance->aiConfig = $container->get('config.factory')->get('ai.settings');
    $instance->genConfig = $container->get('config.factory')->get('ai_image_gen.settings');
    $instance->aiProviderManager = $container->get('ai.provider');
    $instance->twig = $container->get('twig');
    $instance->providerHelper = $container->get('ai_image_gen.provider');
    return $instance;
  }

/**
  * Create an AI image.
  *
  * @param string|null $prompt
  *   The prompt for image generation.
  * @param string $lang_code
  *   The language code.
  *
  * @return \Symfony\Component\HttpFoundation\JsonResponse
  *   JSON response.
  */
public function generate($prompt = NULL, $lang_code = 'en') {
    if (!$prompt) {
        return new JsonResponse([
            'error' => $this->t('A prompt is required to generate an image.'),
        ], 400);
    }

    // Get provider and model
    $data = $this->providerHelper->getSetProvider();
    if (!$data) {
        return new JsonResponse([
            'error' => $this->t('No AI provider found.'),
        ]);
    }
    $provider = $data['provider_id'];
    $model = $data['model_id'];

    // Set configuration for image generation
    $config = [
        "n" => 1,
        "response_format" => "url",
        "size" => "256x256",
        "quality" => "standard",
        "style" => "vivid",
    ];

    $provider->setConfiguration($config);
    $input = new TextToImageInput($prompt_styled);
    $response = $provider->textToImage($input, $model);

    // Create file from the generated image
    $file = $response->getNormalized()[0]->getAsFileEntity();

    // Create media entity
    $media_storage = $this->entityTypeManager->getStorage('media');
    $media = $media_storage->create([
        'bundle' => 'image',
        'name' => $prompt,
        'field_media_image' => [
            'target_id' => $file->id(),
            'alt' => $prompt,
        ],
        'status' => 1,
    ]);
    $media->save();

    return new JsonResponse([
        'media_id' => $media->id(),
        'file_url' => $file->createFileUrl(),
        'message' => $this->t('Image generated successfully'),
    ]);
  }
}

<?php

namespace Drupal\islandora_iiif_presentation_api\Normalizer\V3;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\iiif_presentation_api\Normalizer\EntityUriTrait;
use Drupal\iiif_presentation_api\Normalizer\V3\NormalizerBase;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\islandora\IslandoraUtils;
use Drupal\islandora_iiif_presentation_api\Normalizer\FieldSpecificNormalizerTrait;
use Symfony\Component\Serializer\Exception\LogicException;

/**
 * Normalizer for image items.
 */
class ImageItemNormalizer extends NormalizerBase {

  use EntityUriTrait;
  use FieldSpecificNormalizerTrait;

  /**
   * {@inheritDoc}
   */
  protected $supportedInterfaceOrClass = ImageItem::class;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor for the ImageItemNormalizer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteProviderInterface $route_provider) {
    $this->entityTypeManager = $entity_type_manager;
    $this->setRouteProvider($route_provider);
    $this->supportedReferenceField = 'field_media_image';
    $this->supportedEntityType = 'media';
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (!isset($context['parent'])) {
      throw new LogicException('Normalization requires a parent context.');
    }
    $normalized = [];
    $values = $object->getValue();

    if (isset($values['height'])) {
      $normalized['height'] = (int) $values['height'];
    }
    if (isset($values['width'])) {
      $normalized['width'] = (int) $values['width'];
    }

    $file = $this->entityTypeManager->getStorage('file')->load($values['target_id']);
    if ($file) {
      $this->addCacheableDependency($context, $file);
      $normalized['items'][] = [
        'id' => $context['parent']['id'],
        'type' => 'AnnotationPage',
        'items' => [
          [
            'id' => $this->getEntityUri($file, $context),
            'type' => 'Annotation',
            'body' => [
              'id' => $file->createFileUrl(FALSE),
              'type' => 'Image',
              'format' => $file->getMimeType(),
            ],
            'height' => (int) $normalized['height'],
            'width' => (int) $normalized['width'],
            'target' => $context['parent']['id'],
          ],
        ],
      ];
    }
    return $normalized;
  }

  /**
   * {@inheritDoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      ImageItem::class => TRUE,
    ];
  }

}

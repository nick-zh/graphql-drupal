<?php

namespace Drupal\graphql_content_mutation\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CreateEntityDeriver extends DeriverBase implements ContainerDeriverInterface {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entityTypeId => $type) {
      if (!($type instanceof ContentEntityTypeInterface)) {
        continue;
      }

      foreach ($this->entityTypeBundleInfo->getBundleInfo($entityTypeId) as $bundleName => $bundle) {
        $this->derivatives["$entityTypeId:$bundleName"] = [
          'name' => 'create' . graphql_camelcase([$entityTypeId, $bundleName]),
          'arguments' => [
            'input' => [
              'type' => graphql_camelcase([$entityTypeId, $bundleName]) . 'Input',
              'nullable' => FALSE,
              'multi' => FALSE,
            ],
          ],
          'entity_type' => $entityTypeId,
          'entity_bundle' => $bundleName,
        ] + $basePluginDefinition;
      }
    }

    return parent::getDerivativeDefinitions($basePluginDefinition);
  }

}

<?php

namespace Drupal\graphql_views\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\Plugin\views\display\DisplayPluginInterface;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for graphql view derivers.
 */
abstract class ViewDeriverBase extends DeriverBase implements ContainerDeriverInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The interface plugin manager to search for return type candidates.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $interfacePluginManager;

  /**
   * An key value pair of data tables and the entities they belong to.
   *
   * @var string[]
   */
  protected $dataTables;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('graphql_core.interface_manager')
    );
  }

  /**
   * Creates a ViewDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An entity type manager instance.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $interfacePluginManager
   *   The plugin manager for graphql interfaces.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PluginManagerInterface $interfacePluginManager
  ) {
    $this->interfacePluginManager = $interfacePluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Check if a pager is configured.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return bool
   *   Flag indicating if the view is configured with a pager.
   */
  protected function isPaged(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return isset($pagerOptions['type']) && in_array($pagerOptions['type'], ['full', 'mini']);
  }

  /**
   * Get the configured default limit.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return int
   *   The default limit.
   */
  protected function getPagerLimit(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return NestedArray::getValue($pagerOptions, [
      'options', 'items_per_page',
    ]) ?: 0;
  }

  /**
   * Get the configured default offset.
   *
   * @param \Drupal\views\Plugin\views\display\DisplayPluginInterface $display
   *   The display configuration.
   *
   * @return int
   *   The default offset.
   */
  protected function getPagerOffset(DisplayPluginInterface $display) {
    $pagerOptions = $display->getOption('pager');
    return NestedArray::getValue($pagerOptions, [
      'options', 'offset',
    ]) ?: 0;
  }

  /**
   * Retrieves the entity type id of an entity by its base or data table.
   *
   * @param string $table
   *   The base or data table of an entity.
   *
   * @return string
   *   The id of the entity type that the given base table belongs to.
   */
  protected function getEntityTypeByTable($table) {
    if (!isset($this->dataTables)) {
      $this->dataTables = [];

      foreach ($this->entityTypeManager->getDefinitions() as $entityTypeId => $entityType) {
        if ($dataTable = $entityType->getDataTable()) {
          $this->dataTables[$dataTable] = $entityType->id();
        }
        if ($baseTable = $entityType->getBaseTable()) {
          $this->dataTables[$baseTable] = $entityType->id();
        }
      }
    }

    return !empty($this->dataTables[$table]) ? $this->dataTables[$table] : NULL;
  }

  /**
   * Check if a certain interface exists.
   *
   * @param string $interface
   *   The GraphQL interface name.
   *
   * @return bool
   *   Boolean flag indicating if the interface exists.
   */
  protected function interfaceExists($interface) {
    return (bool) array_filter($this->interfacePluginManager->getDefinitions(), function ($definition) use ($interface) {
      return $definition['name'] === $interface;
    });
  }

  /**
   * Returns a view display object.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The view object.
   * @param string $displayId
   *   The display ID to use.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayPluginInterface
   *   The view display object.
   */
  protected function getViewDisplay(ViewEntityInterface $view, $displayId) {
    $viewExecutable = $view->getExecutable();
    $viewExecutable->setDisplay($displayId);
    return $viewExecutable->getDisplay();
  }

  /**
   * Returns information about view arguments (contextual filters).
   *
   * @param array $viewArguments
   *   The "arguments" option of a view display.
   *
   * @return array
   *   Arguments information keyed by the argument ID. Subsequent array keys:
   *     - index: argument index.
   *     - entity_type: target entity type.
   *     - bundles: target bundles (can be empty).
   */
  protected function getArgumentsInfo(array $viewArguments) {
    $argumentsInfo = [];

    $index = -1;
    foreach ($viewArguments as $argumentId => $argument) {
      $index++;
      $info = [
        'index' => $index,
        'entity_type' => NULL,
        'bundles' => [],
      ];
      if (isset($argument['entity_type']) && isset($argument['entity_field'])) {
        $entityType = $this->entityTypeManager->getDefinition($argument['entity_type']);
        if ($entityType) {
          $idField = $entityType->getKey('id');
          if ($idField === $argument['entity_field']) {
            $info['entity_type'] = $argument['entity_type'];
            if (
              $argument['specify_validation'] &&
              strpos($argument['validate']['type'], 'entity:') === 0 &&
              !empty($argument['validate_options']['bundles'])
            ) {
              $info['bundles'] = $argument['validate_options']['bundles'];
            }
          }
        }
      }
      $argumentsInfo[$argumentId] = $info;
    }

    return $argumentsInfo;
  }

}

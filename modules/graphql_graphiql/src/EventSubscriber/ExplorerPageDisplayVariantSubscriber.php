<?php

namespace Drupal\graphql_graphiql\EventSubscriber;

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Disables any display variant on the explorer page.
 */
class ExplorerPageDisplayVariantSubscriber implements EventSubscriberInterface {
  /**
   * Disables any display variant on the explorer page.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    if ($event->getRouteMatch()->getRouteName() === 'graphql_graphiql.explorer') {
      $event->setPluginId(NULL)->stopPropagation();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }
}

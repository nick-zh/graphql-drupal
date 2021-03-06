<?php

namespace Drupal\graphql_core\Plugin\GraphQL\Fields;


use Drupal\graphql_core\Annotation\GraphQLField;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Get the a specific response header of an internal or external request.
 *
 * @GraphQLField(
 *   id = "response_header",
 *   name = "header",
 *   type = "String",
 *   types = {"InternalResponse", "ExternalResponse"},
 *   arguments={
 *     "key" = "String"
 *   }
 * )
 */
class ResponseHeader extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof Response) {
      yield $value->headers->get($args['key']);
    }

    if ($value instanceof ResponseInterface) {
      yield implode(";", $value->getHeader($args['key']));
    }
  }

}

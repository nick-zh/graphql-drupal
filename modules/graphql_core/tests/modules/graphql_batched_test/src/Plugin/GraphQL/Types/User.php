<?php

namespace Drupal\graphql_batched_test\Plugin\GraphQL\Types;

use Drupal\graphql_core\GraphQL\TypePluginBase;

/**
 * A user type for testing.
 *
 * @GraphQLType(
 *   id = "user",
 *   name = "User"
 * )
 */
class User extends TypePluginBase {

}

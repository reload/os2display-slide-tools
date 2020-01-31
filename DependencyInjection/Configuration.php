<?php

namespace Reload\Os2DisplaySlideTools\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your
 * app/config files.
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('os2_display_slide_tools');

    $rootNode
      ->children()
        ->booleanNode('use_ttl')
          ->defaultFalse()
        ->end()
      ->end();

    return $treeBuilder;
  }

}

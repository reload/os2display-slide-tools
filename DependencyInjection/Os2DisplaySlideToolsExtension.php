<?php
/**
 * @file
 * Bundle integration.
 */

namespace Reload\Os2DisplaySlideTools\DependencyInjection;

use Os2Display\CoreBundle\DependencyInjection\Os2DisplayBaseExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class Os2DisplaySlideToolsExtension extends Os2DisplayBaseExtension
{
  /**
   * {@inheritdoc}
   */
  public function load(array $configs, ContainerBuilder $container)
  {
    $this->dir = __DIR__;

    parent::load($configs, $container);

    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $def = $container->getDefinition('os2display.slidesInSlidesCron');
    // Override the default "false" on the cron runner for a TTL with the
    // config settings.
    $def->replaceArgument(3, $config['use_ttl']);
  }
}

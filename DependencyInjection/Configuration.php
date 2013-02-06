<?php

namespace Scandio\JobQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('scandio_job_queue');

        $rootNode
            ->children()
                ->booleanNode('enable_randomization')
                    ->defaultTrue()
                ->end()
                ->arrayNode('workers')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
            ->end();

        return $treeBuilder;
    }
}

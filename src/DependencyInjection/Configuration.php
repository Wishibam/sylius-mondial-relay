<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wishibam_sylius_mondial_relay_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}

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
        $rootNode
            ->children()
                ->scalarNode('your_place_code')
                    ->cannotBeEmpty()
                    ->defaultValue("11")
                ->end()
                ->scalarNode('brand_mondial_relay_code')
                    ->cannotBeEmpty()
                    ->defaultValue('BDTEST13')
                ->end()
                ->scalarNode('private_key')
                    ->cannotBeEmpty()
                    ->defaultValue('PrivateK')
                ->end()
                ->scalarNode('language')
                    ->defaultValue('FR')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('responsive')
                    ->defaultValue(true)
                ->end()
                ->arrayNode('map')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('type')
                            ->values(['gmap', 'leaflet',])
                            ->defaultValue('leaflet')
                        ->end()
                        ->booleanNode('enableGeolocalisatedSearch')
                            ->defaultValue(true)
                        ->end()
                        ->scalarNode('nbResults')->end()
                        ->scalarNode('googleApiKey')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}

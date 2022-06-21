<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Wishibam\SyliusMondialRelayPlugin\WishibamSyliusMondialRelayPlugin;

final class WishibamSyliusMondialRelayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->registerConfig($configs, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    private function registerConfig(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        $configurationDefinition = new Definition(ParsedConfiguration::class, [
            $config['language'],
            $config['private_key'],
            $config['your_place_code'],
            $config['brand_mondial_relay_code'],
            $config['shipping_code'],
            $config['map'],
            $config['responsive']
        ]);

        $container->setDefinition('wishibam_mondial_relay.parsed_configuration', $configurationDefinition);
    }
}

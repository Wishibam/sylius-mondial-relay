<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;
use Wishibam\SyliusMondialRelayPlugin\WishibamSyliusMondialRelayPlugin;

final class WishibamSyliusMondialRelayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);
        var_dump($config);
        $this->registerParameters($config, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    private function registerParameters(array $config, ContainerBuilder $container): void
    {
        $container->setParameter('wishibam_mondial_relay.your_place_code', $config['your_place_code']);
        $container->setParameter('wishibam_mondial_relay.brand_mondial_relay_code', $config['brand_mondial_relay_code']);
        $container->setParameter('wishibam_mondial_relay.private_key', $config['private_key']);
    }
}

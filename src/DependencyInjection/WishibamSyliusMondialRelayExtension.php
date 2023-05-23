<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ParsedConfiguration;

final class WishibamSyliusMondialRelayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configurations = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $configurationResolver = new Definition(ConfigurationResolver::class);

        foreach ($configurations as $configurationKey => $configuration) {
            $configurationResolver->addMethodCall('registerConfiguration', [
                $configurationKey,
                new Definition(ParsedConfiguration::class, [
                    $configuration['language'],
                    $configuration['private_key'],
                    $configuration['your_place_code'],
                    $configuration['brand_mondial_relay_code'],
                    $configuration['shipping_code'],
                    $configuration['map'],
                    $configuration['responsive'],
                ]),
            ]);
        }

        $container->setDefinition(ConfigurationResolver::SERVICE_ID, $configurationResolver);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}

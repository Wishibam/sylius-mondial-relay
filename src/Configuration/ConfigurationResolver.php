<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Configuration;

class ConfigurationResolver
{
    public const SERVICE_ID = 'wishibam_mondial_relay.configuration_resolver';

    private array $configurations;

    public function __construct(array $configurations = [])
    {
        $this->configurations = $configurations;
    }

    public function registerConfiguration(string $configurationKey, ParsedConfiguration $configuration): void
    {
        $this->configurations[$configurationKey] = $configuration;
    }

    public function getConfiguration(string $configurationKey): ParsedConfiguration
    {
        if (!isset($this->configurations[$configurationKey])) {
            throw new \InvalidArgumentException(sprintf('Could not find a Mondial Relay configuration with key: "%s"', $configurationKey));
        }

        return $this->configurations[$configurationKey];
    }

    public function listConfigurationsKeys(): array
    {
        return array_combine(array_keys($this->configurations), array_keys($this->configurations));
    }
}

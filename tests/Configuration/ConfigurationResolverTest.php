<?php

declare(strict_types=1);

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ParsedConfiguration;

final class ConfigurationResolverTest extends TestCase
{
    use ProphecyTrait;

    private ConfigurationResolver $configurationResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationResolver = new ConfigurationResolver();
    }

    public function testGetSet(): void
    {
        $configuration = $this->prophesize(ParsedConfiguration::class);

        $this->configurationResolver->registerConfiguration('mondial_relay_1', $configuration->reveal());

        self::assertEquals($configuration->reveal(), $this->configurationResolver->getConfiguration('mondial_relay_1'));
    }

    public function testGetUnknownConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find a Mondial Relay configuration with key: "unknown_configuration"');

        $this->configurationResolver->getConfiguration('unknown_configuration');
    }

    public function testListConfigurationsKeys(): void
    {
        $configuration = $this->prophesize(ParsedConfiguration::class);

        $this->configurationResolver->registerConfiguration('mondial_relay_1', $configuration->reveal());
        $this->configurationResolver->registerConfiguration('mondial_relay_2', $configuration->reveal());

        self::assertEquals([
            'mondial_relay_1' => 'mondial_relay_1',
            'mondial_relay_2' => 'mondial_relay_2',
        ], $this->configurationResolver->listConfigurationsKeys());
    }
}

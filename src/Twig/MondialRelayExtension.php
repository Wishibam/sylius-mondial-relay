<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Twig;

use Sylius\Component\Core\Model\ShippingMethodInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodTypeExtension;
use Wishibam\SyliusMondialRelayPlugin\Service\RequestLocaleCheckerInterface;

class MondialRelayExtension extends AbstractExtension
{
    private ConfigurationResolver $configurationResolver;
    private RequestLocaleCheckerInterface $requestLocaleChecker;

    public function __construct(
        ConfigurationResolver $configurationResolver,
        RequestLocaleCheckerInterface $requestLocaleChecker
    ) {
        $this->configurationResolver = $configurationResolver;
        $this->requestLocaleChecker = $requestLocaleChecker;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isRequestLocaleAllowedForMondialRelay', [$this, 'isRequestLocaleAllowedForMondialRelay']),
            new TwigFunction('isMondialRelayShippingMethod', [$this, 'isMondialRelayShippingMethod']),
            new TwigFunction('getMondialRelayConfiguration', [$this, 'getMondialRelayConfiguration']),
        ];
    }

    public function isRequestLocaleAllowedForMondialRelay(): bool
    {
        return $this->requestLocaleChecker->isLocaleAllowed();
    }

    public function isMondialRelayShippingMethod(ShippingMethodInterface $shippingMethod): bool
    {
        return isset($shippingMethod->getConfiguration()[ShippingMethodTypeExtension::CONFIGURATION_KEY]);
    }

    public function getMondialRelayConfiguration(ShippingMethodInterface $shippingMethod): ParsedConfiguration
    {
        return $this->configurationResolver->getConfiguration($shippingMethod->getConfiguration()[ShippingMethodTypeExtension::CONFIGURATION_KEY]);
    }
}

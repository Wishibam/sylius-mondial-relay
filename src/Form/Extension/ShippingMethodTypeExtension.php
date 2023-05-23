<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodType;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;

class ShippingMethodTypeExtension extends AbstractTypeExtension
{
    public const CONFIGURATION_KEY = 'mondial_relay_configuration';

    private const NO_MONDIAL_RELAY_CONFIGURATION = null;

    private ConfigurationResolver $configurationResolver;

    public function __construct(ConfigurationResolver $configurationResolver)
    {
        $this->configurationResolver = $configurationResolver;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ShippingMethodInterface|null $shippingMethod */
        $shippingMethod = $builder->getData();

        $builder
            ->add(self::CONFIGURATION_KEY, ChoiceType::class, [
                'mapped' => false,
                'label' => 'wishibam_mondial_relay.form.shipping_method.mondial_relay_configuration',
                'data' => $shippingMethod->getConfiguration()[self::CONFIGURATION_KEY] ?? null,
                'choices' => array_merge(
                    ['-' => self::NO_MONDIAL_RELAY_CONFIGURATION],
                    $this->configurationResolver->listConfigurationsKeys(),
                ),
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            /** @var ShippingMethodInterface $shippingMethod */
            $shippingMethod = $event->getData();
            $configuration = $shippingMethod->getConfiguration();

            $selectedConfiguration = $event->getForm()->get(self::CONFIGURATION_KEY)->getData();

            if (self::NO_MONDIAL_RELAY_CONFIGURATION !== $selectedConfiguration) {
                $configuration[self::CONFIGURATION_KEY] = $selectedConfiguration;
            } else {
                unset($configuration[self::CONFIGURATION_KEY]);
            }

            $shippingMethod->setConfiguration($configuration);
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShippingMethodType::class];
    }
}

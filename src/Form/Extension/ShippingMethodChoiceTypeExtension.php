<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethod;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\SetMondialRelayParcelPointOnShippingAddressSubscriber;

class ShippingMethodChoiceTypeExtension extends AbstractTypeExtension
{
    public const SEPARATOR_PARCEL_NAME_AND_PARCEL_ID = '---';

    private ShippingMethodsResolverInterface $shippingMethodsResolver;
    private ParsedConfiguration $configuration;
    private RepositoryInterface $repository;

    public function __construct(
        ShippingMethodsResolverInterface $shippingMethodsResolver,
        RepositoryInterface $repository,
        ParsedConfiguration $configuration
    )
    {
        $this->shippingMethodsResolver = $shippingMethodsResolver;
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('parcelPoint', HiddenType::class, [
            'mapped' => false,
            'label' => false,
        ]);
        $builder->add('mondialRelayParcelAddress', AddressType::class, [
            'mapped' => false,
            'label' => false,
        ]);
        // Remove the useless fields from the address type
        $builder->get('mondialRelayParcelAddress')->remove('phoneNumber');
        $builder->get('mondialRelayParcelAddress')->remove('countryCode');
        $builder->get('mondialRelayParcelAddress')->remove('firstName');
        $builder->get('mondialRelayParcelAddress')->remove('lastName');

        $builder->addEventSubscriber(new SetMondialRelayParcelPointOnShippingAddressSubscriber());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['subject']) && $this->shippingMethodsResolver->supports($options['subject'])) {
            $shippingMethods = $this->shippingMethodsResolver->getSupportedMethods($options['subject']);
        } else {
            $shippingMethods  = $this->repository->findAll();
        }

        $mondialRelayShippingMethod = null;
        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if (null === $shippingMethod->getCode()) {
                continue;
            }

            if (false !== strpos($shippingMethod->getCode(), ParsedConfiguration::MONDIAL_RELAY_CODE)) {
                $mondialRelayShippingMethod = $shippingMethod;
                break;
            }
        }

        if (null === $mondialRelayShippingMethod) {
            return;
        }

        $view->vars['mondial_relay.configuration'] = $this->configuration;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}

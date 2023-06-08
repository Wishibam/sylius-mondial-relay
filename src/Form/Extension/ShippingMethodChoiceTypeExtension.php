<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\SetMondialRelayParcelPointOnShippingAddressSubscriber;

class ShippingMethodChoiceTypeExtension extends AbstractTypeExtension
{
    public const SEPARATOR_PARCEL_NAME_AND_PARCEL_ID = '---';

    private ShippingMethodsResolverInterface $shippingMethodsResolver;
    private RepositoryInterface $repository;
    private SessionInterface $session;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        ShippingMethodsResolverInterface $shippingMethodsResolver,
        RepositoryInterface $repository,
        SessionInterface $session,
        EventDispatcherInterface $dispatcher
    ) {
        $this->shippingMethodsResolver = $shippingMethodsResolver;
        $this->repository = $repository;
        $this->session = $session;
        $this->dispatcher = $dispatcher;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null === $this->getMondialRelayShippingMethod($options)) {
            return;
        }

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

        $builder->addEventSubscriber(new SetMondialRelayParcelPointOnShippingAddressSubscriber($this->session, $this->dispatcher));
    }

    private function getMondialRelayShippingMethod(array $options): ?ShippingMethod
    {
        if (isset($options['subject']) && $this->shippingMethodsResolver->supports($options['subject'])) {
            $shippingMethods = $this->shippingMethodsResolver->getSupportedMethods($options['subject']);
        } else {
            $shippingMethods  = $this->repository->findAll();
        }

        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if (null === $shippingMethod->getCode() || !$shippingMethod->isEnabled()) {
                continue;
            }

            if (isset($shippingMethod->getConfiguration()[ShippingMethodTypeExtension::CONFIGURATION_KEY])) {
                return $shippingMethod;
            }
        }

        return null;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}

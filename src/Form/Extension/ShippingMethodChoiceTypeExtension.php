<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\Shipment;
use Sylius\Component\Shipping\Model\ShippingMethod;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('parcelPoint', HiddenType::class, [
            'mapped' => false,
            'label' => false,
        ]);
        $builder->add('mondialRelayParcelAddress', AddressType::class, [
            'mapped' => false,
            'label' => false,
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $shipment = $event->getData();
            $form = $event->getForm();

            /** @var Shipment $shipment */
            if (ParsedConfiguration::MONDIAL_RELAY_CODE !== $shipment->getMethod()->getCode()) {
               return;
            }

            /** @var Address $mondialRelayPointAddress */
            $mondialRelayPointAddress = $form->get('mondialRelayParcelAddress')->getData();
            /** @var Address $originalShippingAddress */
            $originalShippingAddress = $shipment->getOrder()->getShippingAddress();
            // Replace the original shipping address info by the mondial relay parcel point info
            $originalShippingAddress->setPostcode($mondialRelayPointAddress->getPostcode());
            $originalShippingAddress->setStreet($mondialRelayPointAddress->getStreet());
            $originalShippingAddress->setCity($mondialRelayPointAddress->getCity());
            // Example "Amazing shop name---FR-008046"
            $originalShippingAddress->setCompany($mondialRelayPointAddress->getCompany() . self::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID.$form->get('parcelPoint')->getData());
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['subject']) && $this->shippingMethodsResolver->supports($options['subject'])) {
            $shippingMethods = $this->shippingMethodsResolver->getSupportedMethods($options['subject']);
        } else {
            $shippingMethods  = $this->repository->findAll();
        }

        $mondialRelayShippingMethod = null;
        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if (false !== strpos($shippingMethod->getCode(), ParsedConfiguration::MONDIAL_RELAY_CODE)) {
                $mondialRelayShippingMethod = $shippingMethod;
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

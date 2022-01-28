<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber;

use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;

class SetMondialRelayParcelPointOnShippingAddressSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'setMondialRelayPointInsideOrderShippingAddress',
        ];
    }

    public function setMondialRelayPointInsideOrderShippingAddress(FormEvent $event): void
    {
        $shipment = $event->getData();
        $form = $event->getForm();

        if (
            !$shipment instanceof ShipmentInterface ||
            null === $shipment->getMethod() ||
            null === $shipment->getOrder() ||
            ParsedConfiguration::MONDIAL_RELAY_CODE !== $shipment->getMethod()->getCode()
        ) {
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
        $originalShippingAddress->setCompany($mondialRelayPointAddress->getCompany() . ShippingMethodChoiceTypeExtension::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID.$form->get('parcelPoint')->getData());
    }
}

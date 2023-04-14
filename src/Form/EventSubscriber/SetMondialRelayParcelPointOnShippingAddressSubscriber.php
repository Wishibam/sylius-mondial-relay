<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber;

use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;

class SetMondialRelayParcelPointOnShippingAddressSubscriber implements EventSubscriberInterface
{
    public const SESSION_ID = 'mondialRelayPreviousAddress';
    private SessionInterface $session;
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }
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

        /** @var Address $originalShippingAddress */
        $originalShippingAddress = $shipment->getOrder()->getShippingAddress();

        if (
            !$shipment instanceof ShipmentInterface ||
            null === $shipment->getMethod() ||
            null === $shipment->getOrder() ||
            ParsedConfiguration::MONDIAL_RELAY_CODE !== $shipment->getMethod()->getCode()
        ) {
            $this->resetAddress($originalShippingAddress);
            return;
        }

        /** @var Address $mondialRelayPointAddress */
        $mondialRelayPointAddress = $form->get('mondialRelayParcelAddress')->getData();
        $this->saveAddressData($originalShippingAddress);

        // Replace the original shipping address info by the mondial relay parcel point info
        $originalShippingAddress->setPostcode($mondialRelayPointAddress->getPostcode());
        $originalShippingAddress->setStreet($mondialRelayPointAddress->getStreet());
        $originalShippingAddress->setCity($mondialRelayPointAddress->getCity());
        // Example "Amazing shop name---FR-008046"
        $originalShippingAddress->setCompany($mondialRelayPointAddress->getCompany() . ShippingMethodChoiceTypeExtension::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID.$form->get('parcelPoint')->getData());
    }

    private function saveAddressData(Address $address)
    {
        $this->session->set(self::SESSION_ID, [
            'postCode' => $address->getPostcode(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'company' => $address->getCompany()
        ]);
    }

    private function resetAddress(Address $address)
    {
        /** @var null|array{postCode: ?string, street: ?string, city: ?string, company: ?string} $previousAddress */
        $previousAddress = $this->session->get(self::SESSION_ID);

        if (null === $previousAddress) {
            return;
        }

        $address->setCity($previousAddress['city']);
        $address->setPostcode($previousAddress['postCode']);
        $address->setCompany($previousAddress['company']);
        $address->setStreet($previousAddress['street']);

        $this->session->set('mondialRelayPreviousAddress', null);
    }
}

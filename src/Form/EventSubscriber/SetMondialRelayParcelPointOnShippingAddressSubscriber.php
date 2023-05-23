<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber;

use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Event\ResetAddressToPreviousAddress;
use Wishibam\SyliusMondialRelayPlugin\Event\SetMondialRelayInAddress;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;

class SetMondialRelayParcelPointOnShippingAddressSubscriber implements EventSubscriberInterface
{
    public const SESSION_ID = 'mondialRelayPreviousAddress';
    private SessionInterface $session;
    private EventDispatcherInterface $dispatcher;

    public function __construct(SessionInterface $session, EventDispatcherInterface $dispatcher)
    {
        $this->session = $session;
        $this->dispatcher = $dispatcher;
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
            /** @var null|array{postCode: ?string, street: ?string, city: ?string, company: ?string} $previousAddressData */
            $previousAddressData = $this->session->get(self::SESSION_ID);

            if ($previousAddressData !== null) {
                $this->dispatcher->dispatch(new ResetAddressToPreviousAddress($originalShippingAddress, $previousAddressData));
                $this->resetAddress($originalShippingAddress, $previousAddressData);
            }

            return;
        }

        /** @var Address $mondialRelayPointAddress */
        $mondialRelayPointAddress = $form->get('mondialRelayParcelAddress')->getData();

        $this->dispatcher->dispatch($event = new SetMondialRelayInAddress($originalShippingAddress));
        $this->saveAddressData($originalShippingAddress, $event->getPreviousAddressData());

        // Replace the original shipping address info by the mondial relay parcel point info
        $originalShippingAddress->setPostcode($mondialRelayPointAddress->getPostcode());
        $originalShippingAddress->setStreet($mondialRelayPointAddress->getStreet());
        $originalShippingAddress->setCity($mondialRelayPointAddress->getCity());
        // Example "Amazing shop name---FR-008046"
        $originalShippingAddress->setCompany($mondialRelayPointAddress->getCompany() . ShippingMethodChoiceTypeExtension::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID.$form->get('parcelPoint')->getData());
    }

    private function saveAddressData(Address $address, array $data): void
    {
        $data['postCode'] = $address->getPostcode();
        $data['street'] = $address->getStreet();
        $data['city'] = $address->getCity();
        $data['company'] = $address->getCompany();

        $this->session->set(self::SESSION_ID, $data);
    }

    /**
     * @param array{postCode: ?string, street: ?string, city: ?string, company: ?string} $previousAddress
     */
    private function resetAddress(Address $address, array $previousAddress): void
    {
        $address->setCity($previousAddress['city'] ?? null);
        $address->setPostcode($previousAddress['postCode'] ?? null);
        $address->setCompany($previousAddress['company'] ?? null);
        $address->setStreet($previousAddress['street'] ?? null);

        $this->session->set('mondialRelayPreviousAddress', null);
    }
}

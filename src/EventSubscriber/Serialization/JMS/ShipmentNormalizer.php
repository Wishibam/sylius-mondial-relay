<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\EventSubscriber\Serialization\JMS;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sylius\Component\Core\Model\Shipment;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;

class ShipmentNormalizer implements EventSubscriberInterface
{
    private ParsedConfiguration $configuration;

    public function __construct(ParsedConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
                'priority' => 0,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        if (!$event->getObject() instanceof Shipment) {
            return;
        }

        /** @var Shipment $shipment */
        $shipment = $event->getObject();
        if (null === $shipment->getMethod()) {
            return;
        }

        if (ParsedConfiguration::MONDIAL_RELAY_CODE !== $shipment->getMethod()->getCode()) {
            return;
        }

        if (null === $shipment->getOrder() || null === $shipment->getOrder()->getShippingAddress()) {
            return;
        }

        $shippingAddress = $shipment->getOrder()->getShippingAddress();

        list ($parcelId, $company) = explode(
            ShippingMethodChoiceTypeExtension::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID,
            \is_string($shippingAddress->getCompany()) ? strrev($shippingAddress->getCompany()) : ''
        );

        $data = [
            'shipping_code' => $this->configuration->getShippingCode(),
            'place_code' => $this->configuration->getPlaceCode(),
            'parcel_point_id' => $parcelId ? strrev($parcelId) : $parcelId,
            'parcel' => [
                'street' => $shippingAddress->getStreet(),
                'postcode' => $shippingAddress->getPostcode(),
                'city' => $shippingAddress->getCity(),
                'company' => $company ? strrev($company) : $company,
            ],
        ];

        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        $visitor->visitProperty(new StaticPropertyMetadata(Shipment::class, 'mondial_relay_data', $data), $data);
    }
}

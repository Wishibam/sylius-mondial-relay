<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\EventSubscriber\Serialization\JMS;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodTypeExtension;

class ShipmentNormalizer implements EventSubscriberInterface
{
    private ConfigurationResolver $configurationResolver;
    private LoggerInterface $logger;

    public function __construct(ConfigurationResolver $configurationResolver, LoggerInterface $logger)
    {
        $this->configurationResolver = $configurationResolver;
        $this->logger = $logger;
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

        $shipment = $event->getObject();
        $shippingMethod = $shipment->getMethod();

        if (!$shippingMethod instanceof ShippingMethodInterface) {
            return;
        }

        if (!isset($shippingMethod->getConfiguration()[ShippingMethodTypeExtension::CONFIGURATION_KEY])) {
            return;
        }

        if (null === $shipment->getOrder() || null === $shipment->getOrder()->getShippingAddress()) {
            return;
        }

        $configuration = $this->configurationResolver->getConfiguration($shippingMethod->getConfiguration()[ShippingMethodTypeExtension::CONFIGURATION_KEY]);

        $shippingAddress = $shipment->getOrder()->getShippingAddress();

        $pickupPointData = explode(
            ShippingMethodChoiceTypeExtension::SEPARATOR_PARCEL_NAME_AND_PARCEL_ID,
            \is_string($shippingAddress->getCompany()) ? strrev($shippingAddress->getCompany()) : ''
        );

        if (count($pickupPointData) < 2) {
            $this->logger->error(sprintf('[MondialRelayPlugin] Unable to parse pickup point for Order "%d": %s', $shipment->getOrder()->getId(), $shippingAddress->getCompany()));

            return;
        }

        $parcelId = $pickupPointData[0];
        $company = $pickupPointData[1];

        $data = [
            'shipping_code' => $configuration->getShippingCode(),
            'place_code' => $configuration->getPlaceCode(),
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

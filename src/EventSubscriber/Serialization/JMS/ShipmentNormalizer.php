<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\EventSubscriber\Serialization\JMS;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Sylius\Component\Core\Model\Shipment;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;

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
                'class' => Shipment::class,
                'priority' => 0,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $visitor = $event->getVisitor();
        /** @var Shipment $shipment */
        $shipment = $event->getObject();
        if (null === $shipment->getMethod()) {
            return;
        }

        if (ParsedConfiguration::MONDIAL_RELAY_CODE !== $shipment->getMethod()->getCode()) {
            return;
        }

        $data = [
            'mondial_relay_shipping_code' => $this->configuration->getMondialRelayCode(),
            'mondial_relay_parcel_shop_id' => $shipment->getTracking(),
        ];
        $visitor->visitProperty(new StaticPropertyMetadata(Shipment::class, 'mondial_relay_data', $data), $data);
    }
}

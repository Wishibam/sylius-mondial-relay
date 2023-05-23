<?php

namespace Tests\Wishibam\SyliusMondialRelayPlugin\EventSubscriber\Serialization\JMS;

use JMS\Serializer\Accessor\DefaultAccessorStrategy;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GraphNavigator\SerializationGraphNavigator;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\MetadataFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Core\Model\ShippingMethod;
use Sylius\Component\Order\Model\OrderItem;
use Tests\Wishibam\SyliusMondialRelayPlugin\Utils\Reflection;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ConfigurationResolver;
use Wishibam\SyliusMondialRelayPlugin\Configuration\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\EventSubscriber\Serialization\JMS\ShipmentNormalizer;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodTypeExtension;

class ShipmentNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|JsonSerializationVisitor */
    private $visitor;

    /** @var ShipmentNormalizer */
    private $subject;

    /** @var ObjectProphecy|ConfigurationResolver */
    private $configurationResolver;

    protected function setUp(): void
    {
        $this->configurationResolver = $this->prophesize(ConfigurationResolver::class);

        $this->visitor = new JsonSerializationVisitor();
        $this->subject = new ShipmentNormalizer($this->configurationResolver->reveal());

        $driver = $this->prophesize(DriverInterface::class);
        $metadataFactory = new MetadataFactory($driver->reveal());
        $classmetadata = new ClassMetadata(Shipment::class);
        $driver->loadMetadataForClass(Argument::any())->willReturn($classmetadata);
        $navigator = new SerializationGraphNavigator(
            $metadataFactory,
            new HandlerRegistry(),
            new DefaultAccessorStrategy(null)
        );
        $context = new SerializationContext();
        $context->initialize('json', $this->visitor, $navigator, $metadataFactory);
        $navigator->initialize($this->visitor, $context);
        $this->visitor->setNavigator($navigator);
    }

    /**
     * @dataProvider notAShipmentDataProvider
     */
    public function testNotAShipmentQuit($toTest)
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);
        $objectEvent->getObject()->willReturn($toTest);

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNull($data);
    }

    public function testShipmentDoesntHaveRelatedShippingMethodQuit()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);
        $shipment = $this->prophesize(Shipment::class);
        $shipment->getMethod()->willReturn(null);
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNull($data);
    }

    public function testShipmentShippingMethodIsNotMondialRelayQuit()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);
        $shipment = $this->prophesize(Shipment::class);
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getConfiguration()->willReturn([]);
        $shipment->getMethod()->willReturn($shippingMethod->reveal());
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNull($data);
    }

    public function testShipmentHaventOrderRelatedQuit()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);
        $shipment = $this->prophesize(Shipment::class);
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getConfiguration()->willReturn([ShippingMethodTypeExtension::CONFIGURATION_KEY => 'mr_config_1']);
        $shipment->getMethod()->willReturn($shippingMethod->reveal());
        $shipment->getOrder()->willReturn(null);
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNull($data);
    }

    public function testShipmentHaventShippingAddressRelatedQuit()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);
        $shipment = $this->prophesize(Shipment::class);
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getConfiguration()->willReturn([ShippingMethodTypeExtension::CONFIGURATION_KEY => 'mr_config_1']);
        $shipment->getMethod()->willReturn($shippingMethod->reveal());
        $order = $this->prophesize(OrderInterface::class);
        $order->getShippingAddress()->willReturn(null);
        $shipment->getOrder()->willReturn($order->reveal());
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNull($data);
    }

    public function testMondialRelayShipmentHaveAdditionnalDataAddedToSerialization()
    {
        $parsedConfiguration = $this->prophesize(ParsedConfiguration::class);

        $this->configurationResolver->getConfiguration('mr_config_1')->willReturn($parsedConfiguration);

        $objectEvent = $this->prophesize(ObjectEvent::class);
        $objectEvent->getVisitor()->willReturn($this->visitor);
        $shipment = $this->prophesize(Shipment::class);
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getConfiguration()->willReturn([ShippingMethodTypeExtension::CONFIGURATION_KEY => 'mr_config_1']);

        $shipment->getMethod()->willReturn($shippingMethod->reveal());
        $order = $this->prophesize(OrderInterface::class);
        $address = new Address();
        $address->setStreet('42 rue des fleurs');
        $address->setPostcode('12345');
        $address->setCity('Wishiland');
        $address->setCompany('Wishi shoes store---P-123141');
        $order->getShippingAddress()->willReturn($address);
        $parsedConfiguration->getMondialRelayCode()->willReturn('12');
        $parsedConfiguration->getPlaceCode()->willReturn('B1');
        $parsedConfiguration->getShippingCode()->willReturn('24R');
        $shipment->getOrder()->willReturn($order->reveal());
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNotNull($data);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('mondial_relay_data', $data);
        $data = $data['mondial_relay_data'];
        $this->assertEquals([
            'shipping_code' => '24R',
            'place_code' => 'B1',
            'parcel_point_id' => 'P-123141',
            'parcel' => [
                'street' => '42 rue des fleurs',
                'postcode' => '12345',
                'city' => 'Wishiland',
                'company' => 'Wishi shoes store',
            ],
        ], $data);

        $address->setCompany('Wishi shoes store----P-123141');
        $order->getShippingAddress()->willReturn($address);
        $parsedConfiguration->getMondialRelayCode()->willReturn('12');
        $parsedConfiguration->getPlaceCode()->willReturn('B1');
        $parsedConfiguration->getShippingCode()->willReturn('24R');
        $shipment->getOrder()->willReturn($order->reveal());
        $objectEvent->getObject()->willReturn($shipment->reveal());

        $this->subject->onPostSerialize($objectEvent->reveal());

        $data = Reflection::getPrivateProperty($this->visitor, 'data');
        $this->assertNotNull($data);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('mondial_relay_data', $data);
        $data = $data['mondial_relay_data'];
        $this->assertEquals([
            'shipping_code' => '24R',
            'place_code' => 'B1',
            'parcel_point_id' => 'P-123141',
            'parcel' => [
                'street' => '42 rue des fleurs',
                'postcode' => '12345',
                'city' => 'Wishiland',
                'company' => 'Wishi shoes store-',
            ],
        ], $data);
    }

    public function notAShipmentDataProvider()
    {
        return [
            [new \stdClass()],
            [new OrderItem()],
            [0],
            ['what a string'],
            [null],
        ];
    }
}

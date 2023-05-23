<?php
declare(strict_types=1);

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sylius\Bundle\AddressingBundle\Form\Type\AddressType;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodChoiceType;
use Sylius\Component\Addressing\Model\Address as ModelAddress;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Registry\ServiceRegistry;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Sylius\Component\Shipping\Calculator\FlatRateCalculator;
use Sylius\Component\Shipping\Model\ShippingMethod;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
use Wishibam\SyliusMondialRelayPlugin\Event\ResetAddressToPreviousAddress;
use Wishibam\SyliusMondialRelayPlugin\Event\SetMondialRelayInAddress;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\SetMondialRelayParcelPointOnShippingAddressSubscriber;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\ShippingMethodChoiceTypeExtension;

class ShippingMethodChoiceTypeExtensionTest extends TypeTestCase
{
    use ProphecyTrait;

    /** @var ParsedConfiguration */
    private $configuration;

    /** @var RepositoryInterface */
    private $repository;

    /** @var ShippingMethodsResolverInterface */
    private $shippingMethodResolver;

    private $customDispatcher;

    /** @var SessionInterface|ObjectProphecy */
    private $session;

    protected function setUp(): void
    {
        $this->customDispatcher = new EventDispatcher();
        parent::setUp();
    }


    public function testItRegisterSubscriber()
    {
        $model = new Shipment();
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $shippingMethod->getName()->willReturn('some name');
        $shippingMethod->isEnabled()->willReturn(true);
        $this->repository->findAll()->willReturn([$shippingMethod->reveal()]);
        $form = $this->factory->create(ShipmentType::class, $model);
        $postSubmitEvents = $form->getConfig()->getEventDispatcher()->getListeners('form.post_submit');
        $this->assertNotEmpty($postSubmitEvents);

        foreach ($postSubmitEvents as $event) {
            if ($event[0] instanceof SetMondialRelayParcelPointOnShippingAddressSubscriber) {
                return;
            }
        }

        throw new \LogicException('No listener SetMondialRelayParcelPointOnShippingAddressSubscriber registered ');
    }

    public function testItDontAddMondialRelayFieldsIfNotConfigured()
    {
        $model = new Shipment();
        $this->repository->findAll()->willReturn([]);

        $form = $this->factory->create(ShipmentType::class, $model);
        $formNames = array_keys(iterator_to_array($form));
        foreach ($formNames as $formName) {
            $this->assertStringStartsNotWith('mondialRelay', $formName);
        }
    }

    public function testItAddMondialRelayFieldsIfConfigured()
    {
        $model = new Shipment();
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $shippingMethod->getName()->willReturn('some name');
        $shippingMethod->isEnabled()->willReturn(true);
        $this->repository->findAll()->willReturn([$shippingMethod->reveal()]);

        $form = $this->factory->create(ShipmentType::class, $model);
        $formNames = iterator_to_array($form);
        $this->assertArrayHasKey('mondialRelayParcelAddress', $formNames);
    }

    public function testItAddMondialRelayFieldsToForm()
    {
        $model = new Shipment();
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $shippingMethod->getName()->willReturn('some name');
        $shippingMethod->isEnabled()->willReturn(true);
        $this->repository->findAll()->willReturn([$shippingMethod->reveal()]);
        $form = $this->factory->create(ShipmentType::class, $model);

        $this->assertTrue($form->has('parcelPoint'));
        $this->assertTrue($form->has('mondialRelayParcelAddress'));

        $this->assertFalse($form->get('mondialRelayParcelAddress')->has('phoneNumber'));
        $this->assertFalse($form->get('mondialRelayParcelAddress')->has('countryCode'));
        $this->assertFalse($form->get('mondialRelayParcelAddress')->has('firstName'));
        $this->assertFalse($form->get('mondialRelayParcelAddress')->has('lastName'));
    }

    public function testItRegisterConfigurationOnFormViewIfMondialRelayShippingMethodIsAvailable()
    {
        $model = new Shipment();
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $shippingMethod->getCalculator()->willReturn('flat_rate');
        $shippingMethod->getConfiguration()->willReturn(['amount' => 12]);
        $shippingMethod->isEnabled()->willReturn(true);
        $shippingMethod->getName()->willReturn('swaggy delivery');
        $this->repository->findAll()->willReturn([$shippingMethod->reveal()]);

        $form = $this->factory->create(ShipmentType::class, $model);
        $view = $form->createView();
        $this->assertArrayHasKey('mondial_relay.configuration', $view->vars);
        $config = $view->vars['mondial_relay.configuration'];
        $this->assertEquals($this->configuration, $config);
    }

    public function testItDontRegisterConfigurationIfNoMondialRelayShippingMethodIsAvailable()
    {
        $model = new Shipment();
        $this->repository->findAll()->willReturn([]);

        $form = $this->factory->create(ShipmentType::class, $model);
        $view = $form->createView();
        $this->assertArrayNotHasKey('mondial_relay.configuration', $view->vars);
    }

    public function testICanExtendTheAddressAndClearDataWithAnEvent()
    {
        $address = new class() extends Address {
            private ?string $more = 'something';

            public function getMore(): ?string
            {
                return $this->more;
            }

            public function setMore(?string $more): void
            {
                $this->more = $more;
            }
        };
        $this->customDispatcher->addListener(SetMondialRelayInAddress::class, function (SetMondialRelayInAddress $event) {
            $address = $event->getAddress();

            $data = $event->getPreviousAddressData();
            $data['more'] = $address->getMore();
            $address->setMore(null);
            $event->setPreviousAddressData($data);
        });

        $order = new Order();
        $order->setShippingAddress($address);
        $model = new Shipment();
        $model->setOrder($order);
        $shippingMethod = $this->prophesize(ShippingMethod::class);
        $shippingMethod->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $shippingMethod->getName()->willReturn('some name');
        $shippingMethod->isEnabled()->willReturn(true);
        $this->repository->findAll()->willReturn([$shippingMethod->reveal()]);
        $form = $this->factory->create(ShipmentType::class, $model);


        $form->submit(
            [
                'method' => 'mondial-relay',
                'mondialRelayParcelAddress' => [
                    'street' => '419 Rue saint honoré',
                    'city' => 'Paris',
                    'postcode' => '75001',
                ],
                'parcelPoint' => '12345'
            ]
        );

        $this->assertNull($address->getMore());
    }
    public function testICanExtendTheAddressAndReSetThePreviousAddress()
    {
        $address = new class() extends Address {
            private ?string $more = 'something';

            public function getMore(): ?string
            {
                return $this->more;
            }

            public function setMore(?string $more): void
            {
                $this->more = $more;
            }
        };
        $this->customDispatcher->addListener(ResetAddressToPreviousAddress::class, function (ResetAddressToPreviousAddress $event) {
            $data = $event->getPreviousAddressData();
            $address = $event->getAddress();
            $address->setMore($data['more']);
        });

        $order = new Order();
        $order->setShippingAddress($address);
        $model = new Shipment();
        $model->setOrder($order);
        $mondialRelay = $this->prophesize(ShippingMethod::class);
        $mondialRelay->getCode()->willReturn(ParsedConfiguration::MONDIAL_RELAY_CODE);
        $mondialRelay->getName()->willReturn('some name');
        $mondialRelay->isEnabled()->willReturn(true);
        $somethingElse = $this->prophesize(ShippingMethod::class);
        $somethingElse->getCode()->willReturn('something_else');
        $somethingElse->getName()->willReturn('some name');
        $somethingElse->isEnabled()->willReturn(true);
        $this->repository->findAll()->willReturn([$somethingElse->reveal(), $mondialRelay->reveal()]);
        $this->session->get(SetMondialRelayParcelPointOnShippingAddressSubscriber::SESSION_ID)->willReturn([
            'more' => 'Something special',
        ]);
        $this->session->set(SetMondialRelayParcelPointOnShippingAddressSubscriber::SESSION_ID, null)->shouldBeCalled();
        $form = $this->factory->create(ShipmentType::class, $model);


        $form->submit(
            [
                'method' => 'something_else',
            ]
        );

        $this->assertEquals('Something special', $address->getMore());
    }

    protected function getTypeExtensions()
    {
        $this->shippingMethodResolver = $this->prophesize(ShippingMethodsResolverInterface::class);
        $this->repository = $this->prophesize(RepositoryInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);

        $this->configuration = new ParsedConfiguration(
            'FR',
            'private_key',
            'placeCode',
            'mondial1234',
            '24R',
            ['type' => 'leaflet'],
            true
        );
        $subject = new ShippingMethodChoiceTypeExtension(
            $this->shippingMethodResolver->reveal(),
            $this->repository->reveal(),
            $this->configuration,
            $this->session->reveal(),
            $this->customDispatcher
        );

        return [
            $subject,
        ];
    }

    protected function getTypes()
    {
        $addressType = new AddressType(ModelAddress::class, [], new class implements EventSubscriberInterface {
            public static function getSubscribedEvents()
            {
                return [];
            }
        });

        $calculators = new ServiceRegistry(CalculatorInterface::class);
        $calculators->register('flat_rate', new FlatRateCalculator());
        $this->shippingMethodResolver->supports(Argument::any())->willReturn(false);
        $shippingMethodChoiceType = new ShippingMethodChoiceType(
            $this->shippingMethodResolver->reveal(),
            $calculators,
            $this->repository->reveal()
        );

        return [
            $shippingMethodChoiceType,
            new ShipmentType(Shipment::class),
            $addressType
        ];
    }
}

<?php
declare(strict_types=1);

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;
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

    public function testItRegisterSubscriber()
    {
        $model = new Shipment();
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

    public function testItAddMondialRelayFieldsToForm()
    {
        $model = new Shipment();
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

    protected function getTypeExtensions()
    {
        $this->shippingMethodResolver = $this->prophesize(ShippingMethodsResolverInterface::class);
        $this->repository = $this->prophesize(RepositoryInterface::class);

        $this->configuration = new ParsedConfiguration(
            'FR',
            'private_key',
            'placeCode',
            'mondial1234',
            ['type' => 'leaflet'],
            true
        );
        $subject = new ShippingMethodChoiceTypeExtension($this->shippingMethodResolver->reveal(), $this->repository->reveal(), $this->configuration);

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

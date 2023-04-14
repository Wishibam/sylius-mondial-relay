<?php

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\AddressType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wishibam\SyliusMondialRelayPlugin\Form\Extension\AddressTypeExtension;

class AddressTypeExtensionTest extends TestCase
{
    use ProphecyTrait;
    public function testItAddsSubscriberToAddressType()
    {
        $this->assertTrue(in_array(AddressType::class, AddressTypeExtension::getExtendedTypes()));
        $extension = new AddressTypeExtension($this->prophesize(SessionInterface::class)->reveal());

        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder->addEventSubscriber(Argument::cetera())->shouldBeCalled();
        $extension->buildForm($builder->reveal(), []);
    }
}

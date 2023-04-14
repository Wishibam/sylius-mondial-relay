<?php

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\ForgetPreviousAddressOnAddressFormValidationSubscriber;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\SetMondialRelayParcelPointOnShippingAddressSubscriber;

class ForgetPreviousAddressOnAddressFormValidationSubscriberTest extends TestCase
{
    use ProphecyTrait;
    public function testItClearSessionOnFormSubmit()
    {
        $session = $this->prophesize(SessionInterface::class);
        $this->assertArrayHasKey(FormEvents::POST_SUBMIT, ForgetPreviousAddressOnAddressFormValidationSubscriber::getSubscribedEvents());
        $session->set(SetMondialRelayParcelPointOnShippingAddressSubscriber::SESSION_ID, null)->shouldBeCalled();

        $listener = new ForgetPreviousAddressOnAddressFormValidationSubscriber($session->reveal());
        $listener->forgetPreviousAddress($this->prophesize(FormEvent::class)->reveal());
    }

}

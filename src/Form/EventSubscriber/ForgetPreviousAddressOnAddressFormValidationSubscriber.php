<?php

namespace Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ForgetPreviousAddressOnAddressFormValidationSubscriber implements EventSubscriberInterface
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'forgetPreviousAddress',
        ];
    }

    public function forgetPreviousAddress(FormEvent $event): void
    {
        $this->session->set(SetMondialRelayParcelPointOnShippingAddressSubscriber::SESSION_ID, null);
    }
}

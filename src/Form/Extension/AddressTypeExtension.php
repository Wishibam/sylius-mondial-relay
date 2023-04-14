<?php

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\AddressType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wishibam\SyliusMondialRelayPlugin\Form\EventSubscriber\ForgetPreviousAddressOnAddressFormValidationSubscriber;

class AddressTypeExtension extends AbstractTypeExtension
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new ForgetPreviousAddressOnAddressFormValidationSubscriber($this->session));
    }

    public static function getExtendedTypes(): iterable
    {
        return [AddressType::class];
    }
}

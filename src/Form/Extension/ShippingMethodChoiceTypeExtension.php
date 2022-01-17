<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethod;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Wishibam\SyliusMondialRelayPlugin\DependencyInjection\ParsedConfiguration;

class ShippingMethodChoiceTypeExtension extends AbstractTypeExtension
{
    private ShippingMethodsResolverInterface $shippingMethodsResolver;
    private ParsedConfiguration $configuration;
    private RepositoryInterface $repository;

    public function __construct(
        ShippingMethodsResolverInterface $shippingMethodsResolver,
        RepositoryInterface $repository,
        ParsedConfiguration $configuration
    )
    {
        $this->shippingMethodsResolver = $shippingMethodsResolver;
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tracking', HiddenType::class, []);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['subject']) && $this->shippingMethodsResolver->supports($options['subject'])) {
            $shippingMethods = $this->shippingMethodsResolver->getSupportedMethods($options['subject']);
        } else {
            $shippingMethods  = $this->repository->findAll();
        }

        $mondialRelayShippingMethod = null;
        /** @var ShippingMethod $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if (false !== strpos($shippingMethod->getCode(), 'mondial_relay')) {
                $mondialRelayShippingMethod = $shippingMethod;
            }
        }

        if (null === $mondialRelayShippingMethod) {
            return;
        }

        $view->vars['mondial_relay.configuration'] = $this->configuration;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}

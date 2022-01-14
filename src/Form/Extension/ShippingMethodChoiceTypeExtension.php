<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ShipmentType;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Shipping\Model\ShippingMethod;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ShippingMethodChoiceTypeExtension extends AbstractTypeExtension
{
    private string $yourPlaceCode;
    private string $brandMondialRelayCode;
    private string $privateKey;

    /** @var ShippingMethodsResolverInterface */
    private $shippingMethodsResolver;

    /** @var RepositoryInterface */
    private $repository;

    public function __construct(
        ShippingMethodsResolverInterface $shippingMethodsResolver,
        RepositoryInterface $repository,
        string $yourPlaceCode,
        string $brandMondialRelayCode,
        string $privateKey)
    {
        $this->shippingMethodsResolver = $shippingMethodsResolver;
        $this->repository = $repository;
        $this->yourPlaceCode = $yourPlaceCode;
        $this->brandMondialRelayCode = $brandMondialRelayCode;
        $this->privateKey = $privateKey;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        // Add configured variables to the form view
        // This will allow us to render the picking map
        $view->vars['mondial_relay_your_place_code'] = $this->yourPlaceCode;
        $view->vars['mondial_relay_brand_mondial_relay_code'] = $this->brandMondialRelayCode;
        $view->vars['mondial_relay_private_key'] = $this->privateKey;
        $view->vars['mondial_relay_shipping_method'] = $mondialRelayShippingMethod;
    }

    public static function getExtendedTypes(): iterable
    {
        return [ShipmentType::class];
    }
}

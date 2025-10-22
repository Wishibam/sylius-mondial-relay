<?php

namespace Wishibam\SyliusMondialRelayPlugin\Entity;


use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Core\Model\ShipmentInterface;

interface WhishibamMondialRelayShipmentInterface extends ShipmentInterface
{
    public function getShippingAddress(): ?Address;
    public function getSelfShippingAddress(): ?Address;
}

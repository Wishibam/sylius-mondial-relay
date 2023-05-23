<?php

namespace Wishibam\SyliusMondialRelayPlugin\Event;

use Sylius\Component\Core\Model\Address;
use Symfony\Contracts\EventDispatcher\Event;

class ResetAddressToPreviousAddress extends Event
{
    private Address $address;

    private array $previousAddressData;

    public function __construct(Address $address, array $previousAddressData)
    {
        $this->address = $address;
        $this->previousAddressData = $previousAddressData;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getPreviousAddressData(): array
    {
        return $this->previousAddressData;
    }
}

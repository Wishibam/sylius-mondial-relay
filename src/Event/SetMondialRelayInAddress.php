<?php

namespace Wishibam\SyliusMondialRelayPlugin\Event;

use Sylius\Component\Core\Model\Address;
use Symfony\Contracts\EventDispatcher\Event;

class SetMondialRelayInAddress extends Event
{
    private Address $address;
    private array $previousAddressData;

    public function __construct(Address $address)
    {
        $this->address = $address;
        $this->previousAddressData = [];
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getPreviousAddressData(): array
    {
        return $this->previousAddressData;
    }

    public function setPreviousAddressData(array $data): void
    {
        $this->previousAddressData = $data;
    }
}

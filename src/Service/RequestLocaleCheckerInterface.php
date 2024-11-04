<?php

namespace Wishibam\SyliusMondialRelayPlugin\Service;

interface RequestLocaleCheckerInterface
{
    public function isLocaleAllowed(): bool;
}

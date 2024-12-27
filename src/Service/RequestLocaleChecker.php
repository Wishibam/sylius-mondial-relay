<?php

declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestLocaleChecker implements RequestLocaleCheckerInterface
{
    private const ALLOWED_LOCALES = [
        'fr',
        'fr_FR',
        'en_GB',
        'es_ES',
        'nl_NL',
    ];

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function isLocaleAllowed(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return false;
        }

        return in_array($request->getLocale(), self::ALLOWED_LOCALES, true);
    }
}

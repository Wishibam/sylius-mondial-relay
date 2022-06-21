<?php
declare(strict_types=1);

namespace Wishibam\SyliusMondialRelayPlugin\DependencyInjection;

class ParsedConfiguration
{
    public const MAP_TYPE_LEAFLET = 'leaflet';
    public const MAP_TYPE_GOOGLE = 'google';
    public const MONDIAL_RELAY_CODE = 'mondial-relay';

    private string $language;
    private string $mondialRelayCode;
    private string $privateKey;
    private string $placeCode;
    private string $mapType;
    private string $shippingCode;

    private ?string $googleApiKey;

    private int $nbMapResults;
    private bool $responsive;
    private bool $geolocalisedSearch;

    public function __construct(
        string $language,
        string $privateKey,
        string $placeCode,
        string $mondialRelayCode,
        string $shippingCode,
        array $map,
        bool $responsive = true,
        string $googleKey = null
    )
    {
        $this->language = strtoupper($language);
        $this->privateKey = $privateKey;
        $this->placeCode = $placeCode;
        $this->responsive = $responsive;
        $this->mondialRelayCode = $mondialRelayCode;
        $this->shippingCode = $shippingCode;
        $this->geolocalisedSearch = $map['enableGeolocalisatedSearch'] ?? true;
        $this->mapType = $map['type'] ?? 'leaflet';
        if (!in_array($this->mapType, [self::MAP_TYPE_GOOGLE, self::MAP_TYPE_LEAFLET], true)) {
            throw new \LogicException("Type $this->mapType is invalid");
        }

        if ($this->mapType === self::MAP_TYPE_GOOGLE && (null === $googleKey || '' === trim($googleKey))) {
            throw new \LogicException("Key 'map.googleApiKey' must be configured when using the google map");
        }
        $this->nbMapResults = $map['nbResults'] ?? 7; // 7 is the default value
        $this->googleApiKey = $googleKey;
    }

    public function getGoogleApiKey(): ?string
    {
        return $this->googleApiKey;
    }

    public function getNbMapResults(): int
    {
        return $this->nbMapResults;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getMapType(): string
    {
        return $this->mapType;
    }

    public function getGeolocalisedSearch(): bool
    {
        return $this->geolocalisedSearch;
    }

    public function getMondialRelayCode(): string
    {
        return $this->mondialRelayCode;
    }

    public function getShippingCode(): string
    {
        return $this->shippingCode;
    }
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPlaceCode(): string
    {
        return $this->placeCode;
    }

    public function isResponsive(): bool
    {
        return $this->responsive;
    }

    public function isGeolocalisedSearch(): bool
    {
        return $this->geolocalisedSearch;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Wishibam\SyliusMondialRelayPlugin\Utils;

class Reflection
{
    public static function setPrivateProperty(object $object, string $property, $value): void
    {
        $ref = new \ReflectionClass($object);
        $property = $ref->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * @throws \ReflectionException
     *
     * @return mixed
     */
    public static function getPrivateProperty(object $object, string $property)
    {
        $ref = new \ReflectionClass($object);
        $property = $ref->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public static function callPrivateMethod(object $object, string $method, ...$args)
    {
        $method = new \ReflectionMethod($object, $method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}

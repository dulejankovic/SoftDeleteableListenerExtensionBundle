<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter;


/**
 * Class ObjectGetterInterface
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter
 */
interface ObjectGetterInterface
{
    public function getObjects($namespace, $entity, $property): array;
}
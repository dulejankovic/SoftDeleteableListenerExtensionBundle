<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Xcentric\SoftDeleteableExtensionBundle\Entity\ClassConfig;

/**
 * Class ObjectGetterFactory
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter
 */
class ObjectGetterFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ConfigBuilderFactory constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $type
     * @return ObjectGetterInterface
     */
    public function instance($type): ObjectGetterInterface
    {
        switch ($type) {
            case ClassConfig::RELATION_TYPE_MANY_TO_ONE:
                return $this->container->get('many_to_one.objects.getter');
                break;
            case ClassConfig::RELATION_TYPE_MANY_TO_MANY:
                return $this->container->get('many_to_many.objects.getter');
            default:
                throw new \RuntimeException("Type {$type} does not exist.");
        }
    }
}
<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\ObjectGetterInterface;

/**
 * Class ManyToOneObjectGetter
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\Strategy
 */
class ManyToOneObjectGetter implements ObjectGetterInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getObjects($namespace, $entity, $property): array
    {
        return $this->entityManager->getRepository($namespace)->findBy(array(
            $property->name => $entity,
        ));
    }
}
<?php

namespace Xcentric\SoftDeleteableExtensionBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Xcentric\SoftDeleteableExtensionBundle\Services\Delete;

/**
 * Class SoftDeleteListener
 * @package Xcentric\SoftDeleteableExtensionBundle\EventListener
 */
class SoftDeleteListener
{
    use ContainerAwareTrait;

    /**
     * @param LifecycleEventArgs $args
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function preSoftDelete(LifecycleEventArgs $args)
    {
        $processor = new Delete();
        $processor->setContainer($this->container);
        $processor->setEntityManager($args->getEntityManager());
        $processor->process($args->getEntity());
    }
}

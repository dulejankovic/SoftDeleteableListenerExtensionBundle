<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Services;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteUknownTypeException;

class UnDelete extends AbstractProcess
{
    /**
     * @param $entity
     * @param $objects
     * @param $namespace
     * @param $property
     * @param $deleteType
     * @throws OnSoftDeleteUknownTypeException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    protected function processObjects($entity, $objects, $namespace, $property, $deleteType)
    {
        /** @var ClassMetadataFactory $factory */
        $factory = $this->em->getMetadataFactory();
        $cacheDriver = $factory->getCacheDriver();
        $cacheId = ExtensionMetadataFactory::getCacheId($namespace, 'Gedmo\SoftDeleteable');
        $softDelete = false;
        if (($config = $cacheDriver->fetch($cacheId)) !== false) {
            $softDelete = isset($config['softDeleteable']) && $config['softDeleteable'];
        }
        $meta = $this->em->getClassMetadata($namespace);
        foreach ($objects as $object) {
            if (strtoupper($deleteType) === 'SET NULL') {
                $reflProp = $meta->getReflectionProperty($property->name);

                $reflProp->setValue($object, $entity);
                $this->em->persist($object);

            } elseif (strtoupper($deleteType) === 'CASCADE' && $softDelete) {
                $this->cascade($config, $object);
            } else {
                throw new OnSoftDeleteUknownTypeException($deleteType);
            }
        }
    }

    /**
     * @param $config
     * @param $object
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    protected function cascade($config, $object)
    {
        $meta = $this->em->getClassMetadata(get_class($object));
        $reflProp = $meta->getReflectionProperty($config['fieldName']);
        $oldValue = $reflProp->getValue($object);

        if ($oldValue === null) {
            return;
        }

        //check next level
        $this->process($object);

        $reflProp->setValue($object, null);
    }
}

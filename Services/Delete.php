<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Services;

use Gedmo\Mapping\ExtensionMetadataFactory;
use Xcentric\SoftDeleteableExtensionBundle\Entity\DeletableAware;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteNotDeletableAssociationException;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteUknownTypeException;

class Delete extends AbstractProcess
{
    /**
     * @param $entity
     * @param $objects
     * @param $namespace
     * @param $property
     * @param $deleteType
     * @throws OnSoftDeleteUknownTypeException
     */
    protected function processObjects($entity, $objects, $namespace, $property, $deleteType)
    {
        $uow = $this->em->getUnitOfWork();
        $factory = $this->em->getMetadataFactory();
        $cacheDriver = $factory->getCacheDriver();
        $cacheId = ExtensionMetadataFactory::getCacheId($namespace, 'Gedmo\SoftDeleteable');
        $softDelete = false;
        if (($config = $cacheDriver->fetch($cacheId)) !== false) {
            $softDelete = isset($config['softDeleteable']) && $config['softDeleteable'];
        }
        $meta = $this->em->getClassMetadata($namespace);
        foreach ($objects as $object) {
            if ($object instanceof DeletableAware && !$object->isDeletable()) {
                throw new OnSoftDeleteNotDeletableAssociationException('Association: ' . $meta->name . ' not deletable.');
            }

            if (strtoupper($deleteType) === 'SET NULL') {
                $reflProp = $meta->getReflectionProperty($property->name);
                $oldValue = $reflProp->getValue($object);

                $reflProp->setValue($object, null);
                $this->em->persist($object);

                $uow->propertyChanged($object, $property->name, $oldValue, null);
                $uow->scheduleExtraUpdate($object, array(
                    $property->name => array($oldValue, null),
                ));
            } elseif (strtoupper($deleteType) === 'CASCADE') {
                if ($softDelete) {
                    $this->cascade($config, $object);
                } else {
                    $this->em->remove($object);
                }
            } else {
                throw new OnSoftDeleteUknownTypeException($deleteType);
            }
        }
    }

    protected function cascade($config, $object)
    {
        $meta = $this->em->getClassMetadata(get_class($object));
        $reflProp = $meta->getReflectionProperty($config['fieldName']);
        $oldValue = $reflProp->getValue($object);
        if ($oldValue instanceof \Datetime) {
            return;
        }

        //check next level
        $this->process($object);

        $date = new \DateTime();
        $reflProp->setValue($object, $date);

        $uow = $this->em->getUnitOfWork();
        $uow->propertyChanged($object, $config['fieldName'], $oldValue, $date);
        $uow->scheduleExtraUpdate($object, array(
            $config['fieldName'] => array($oldValue, $date),
        ));
    }
}

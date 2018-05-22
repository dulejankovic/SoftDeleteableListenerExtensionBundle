<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\JoinColumn;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Xcentric\SoftDeleteableExtensionBundle\Entity\ClassConfig;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteUnknownTypeException;
use Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\ObjectGetterInterface;


/**
 * Class ProcessByMap
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor
 */
class ProcessByConfig
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * ConfigBuilderFactory constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     * @param $configData
     * @throws \ReflectionException
     * @throws OnSoftDeleteUnknownTypeException
     */
    public function processEntityByConfig($entity, $configData)
    {
        /** @var ClassConfig $config */
        foreach ($configData as $config){

            $reflectionClass = new \ReflectionClass($config->getClass());
            $property = $reflectionClass->getProperty($config->getProperty());

            /** @var ObjectGetterInterface $objectsGetter */
            $objectsGetter = $this->container->get('objects.getter.factory')->instance($config->getRelationType());

            $objects = $objectsGetter->getObjects($config->getClass(), $entity, $property);

            if(isset($objects) && !empty($objects)){
                $this->processObjects($objects, $config->getClass(), $property, $config->getOnDeleteType());
            }
        }
    }

    /**
     * @param $objects
     * @param $namespace
     * @param $property
     * @param $deleteType
     * @throws OnSoftDeleteUnknownTypeException
     */
    protected function processObjects($objects, $namespace, $property, $deleteType)
    {
        $uow = $this->entityManager->getUnitOfWork();
        $factory = $this->entityManager->getMetadataFactory();
        $cacheDriver = $factory->getCacheDriver();
        $cacheId = ExtensionMetadataFactory::getCacheId($namespace, 'Gedmo\SoftDeleteable');
        $softDelete = false;
        if (($config = $cacheDriver->fetch($cacheId)) !== false) {
            $softDelete = isset($config['softDeleteable']) && $config['softDeleteable'];
        }
        $meta = $this->entityManager->getClassMetadata($namespace);
        foreach ($objects as $object) {
            if (strtoupper($deleteType) === 'SET NULL') {
                $reflProp = $meta->getReflectionProperty($property->name);
                $oldValue = $reflProp->getValue($object);

                $reflProp->setValue($object, null);
                $this->entityManager->persist($object);

                $uow->propertyChanged($object, $property->name, $oldValue, null);
                $uow->scheduleExtraUpdate($object, array(
                    $property->name => array($oldValue, null),
                ));
            } elseif (strtoupper($deleteType) === 'CASCADE') {
                if ($softDelete) {
                    $this->softDeleteCascade($config, $object);
                } else {
                    $this->entityManager->remove($object);
                }
            } else {
                throw new OnSoftDeleteUnknownTypeException($deleteType);
            }
        }
    }

    protected function softDeleteCascade($config, $object)
    {
        $meta = $this->entityManager->getClassMetadata(get_class($object));
        $reflProp = $meta->getReflectionProperty($config['fieldName']);
        $oldValue = $reflProp->getValue($object);
        if ($oldValue instanceof \Datetime) {
            return;
        }

        //check next level
        $args = new LifecycleEventArgs($object, $this->entityManager);
        $this->container->get('xcentric.softdeletale.listener.softdelete')->preSoftDelete($args);

        $date = new \DateTime();
        $reflProp->setValue($object, $date);

        $uow = $this->entityManager->getUnitOfWork();
        $uow->propertyChanged($object, $config['fieldName'], $oldValue, $date);
        $uow->scheduleExtraUpdate($object, array(
            $config['fieldName'] => array($oldValue, $date),
        ));
    }
}
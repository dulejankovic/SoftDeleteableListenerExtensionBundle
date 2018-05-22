<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\Strategy;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\ObjectGetterInterface;

/**
 * Class ManyToManyObjectGetter
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ObjectGetter\Strategy
 */
class ManyToManyObjectGetter implements ObjectGetterInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $namespace
     * @param $entity
     * @param $property
     * @return array
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getObjects($namespace, $entity, $property): array
    {
        $reader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass($namespace);
        $entityReflection = new \ReflectionObject($entity);

        $qb = $this->entityManager->getRepository($namespace)->createQueryBuilder('q')
            ->join('q.' . $property->name, 'j');

        /** @var JoinTable $joinTable */
        $joinTable = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\JoinTable');

        if(!$joinTable){
            throw new \Exception('No joinTable found for the relationship ' . $namespace. '#'. $property->name);
        }

        $columns = $joinTable->joinColumns;
        $inversedColumns = $joinTable->inverseJoinColumns;

        if (count($columns) > 1) {
            throw new \Exception('Only one joinColumn is supported!');
        }

        if (count($inversedColumns) > 1) {
            throw new \Exception('Only one inversedJoinColumns is supported!');
        }

        /** @var JoinColumn $joinColumn */
        $joinColumn = $columns[0];
        $joinProperty = $this->getPropertyByColumName($reflectionClass, $joinColumn);

        /** @var JoinColumn $joinColumn */
        $inversedColumn = $inversedColumns[0];
        $inversedJoinProperty = $this->getPropertyByColumName($entityReflection, $inversedColumn);



        if (!$joinProperty){
            throw new \Exception('No joinColumn found for the relationship between ' .$namespace . ' and '. get_class($entity));
        }


        if (!$inversedJoinProperty){
            throw new \Exception('No joinColumn found for the relationship between ' .$namespace . ' and '. get_class($entity));
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $joinValue = $propertyAccessor->getValue($entity, $inversedJoinProperty->name);

        $qb->where($qb->expr()->eq('j.'.$joinProperty->name,$joinValue ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \ReflectionClass $entityReflection
     * @param $name
     * @return \ReflectionProperty
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    private function getPropertyByColumName(\ReflectionClass $entityReflection, $name){

        $reader = new AnnotationReader();

        foreach ($entityReflection->getProperties() as $p) {
            /** @var $column Column */
            if (($id = $reader->getPropertyAnnotation($p, Id::class)) &&
                ($column = $reader->getPropertyAnnotation($p, Column::class)) &&
                $column->name == $name
            ) {

                return $p;
            }
        }
    }
}
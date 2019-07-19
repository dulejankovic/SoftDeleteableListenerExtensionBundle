<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractProcess
{
    use ContainerAwareTrait;

    protected abstract function processObjects($entity, $object, $namespace, $property, $deleteType);

    /** @var EntityManagerInterface */
    protected $em;

    private $processor;

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param $entity
     * @return bool
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \ReflectionException
     */
    public function process($entity)
    {
        $entityReflection = new \ReflectionObject($entity);

        if($this->container->hasParameter('xcentric_class_map_path')) {
            if (($file = $this->container->getParameter('xcentric_class_map_path')) && file_exists($file)) {
                $namespaces = Yaml::parse(file_get_contents($file));
            }
        }

        if(isset($namespaces)){
            $namespaces = isset($namespaces[$entityReflection->getName()]) ? $namespaces[$entityReflection->getName()] : array();
            return $this->processNamespacesFromMap($namespaces, $entity);
        }else{
            $namespaces = $this->em->getConfiguration()
                ->getMetadataDriverImpl()
                ->getAllClassNames();
        }

        $reader = new AnnotationReader();
        foreach ($namespaces as $namespace) {
            $reflectionClass = new \ReflectionClass($namespace);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            foreach ($reflectionClass->getProperties() as $property) {
                if ($onDelete = $reader->getPropertyAnnotation($property, 'Xcentric\SoftDeleteableExtensionBundle\Mapping\Annotation\onSoftDelete')) {
                    $objects = null;
                    $manyToMany = null;
                    $manyToOne = null;
                    if (($manyToOne = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) || ($manyToMany = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany'))) {

                        if($manyToOne)
                            $relationship = $manyToOne;
                        else
                            $relationship = $manyToMany;

                        $ns = null;
                        $nsOriginal = $relationship->targetEntity;
                        $nsFromRelativeToAbsolute = $entityReflection->getNamespaceName().'\\'.$relationship->targetEntity;
                        $nsFromRoot = '\\'.$relationship->targetEntity;
                        if(class_exists($nsOriginal)){
                            $ns = $nsOriginal;
                        }
                        elseif(class_exists($nsFromRoot)){
                            $ns = $nsFromRoot;
                        }
                        elseif(class_exists($nsFromRelativeToAbsolute)){
                            $ns = $nsFromRelativeToAbsolute;
                        }

                        if ($manyToOne && $ns && $entity instanceof $ns) {

                            $objects = $this->getManyToOneObjects($namespace, $entity, $property);
                        }
                        elseif($manyToMany) {

                            if (strtoupper($onDelete->type) === 'SET NULL') {
                                throw new \Exception('SET NULL is not supported for ManyToMany relationships');
                            }

                            $objects = $this->getManyToManyObjects($namespace, $entity, $property);

                        }
                    }

                    if ($objects) {

                        $this->processObjects($entity, $objects, $namespace, $property, $onDelete->type);
                    }
                }
            }
        }
    }

    /**
     * @param $namespaceList
     * @param $entity
     * @return bool
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    protected function processNamespacesFromMap($namespaceList, $entity): bool
    {
        foreach ($namespaceList as $propertyName => $namespaces){
            foreach ($namespaces as $namespace){
                $reflectionClass = new \ReflectionClass($namespace['namespace']);
                $property = $reflectionClass->getProperty($propertyName);

                if ($onDelete = $namespace['onDeleteType']) {
                    if (('manyToOne' == $namespace['relationType']) || 'oneToOne' == $namespace['relationType']) {

                        $objects = $this->getManyToOneObjects($namespace['namespace'], $entity, $property);

                    } elseif ('manyToMany' == $namespace['relationType']) {
                        if (strtoupper($onDelete) === 'SET NULL') {
                            throw new \Exception('SET NULL is not supported for ManyToMany relationships');
                        }

                        $objects = $this->getManyToManyObjects($namespace['namespace'], $entity, $property);
                    }
                }

                if($objects){
                    $this->processObjects($entity, $objects, $namespace['namespace'], $property, $onDelete);
                }
            }
        }

        return true;
    }

    /**
     * @param $namespace
     * @param $entity
     * @param $property
     * @return array
     */
    protected function getManyToOneObjects($namespace, $entity, $property)
    {
        return $this->em->getRepository($namespace)->findBy(array(
            $property->name => $entity,
        ));
    }

    /**
     * @param $namespace
     * @param $entity
     * @param $property
     * @return mixed
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    protected function getManyToManyObjects($namespace, $entity, $property)
    {
        $reader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass($namespace);
        $entityReflection = new \ReflectionObject($entity);

        $qb = $this->em->getRepository($namespace)->createQueryBuilder('q')
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

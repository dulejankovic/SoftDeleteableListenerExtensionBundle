<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\Config\Builder;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Xcentric\SoftDeleteableExtensionBundle\Entity\ClassConfig;
use Xcentric\SoftDeleteableExtensionBundle\Service\Config\ConfigBuilder;


/**
 * Class Annotation
 * @package Xcentric\SoftDeleteableExtensionBundle\Service\Config\Builder
 */
class Annotation implements ConfigBuilder
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $className
     * @return array
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function buildByClassName(string $className): array
    {
        $configs = array();
        $em = $this->container->get('doctrine.orm.entity_manager');

        if (!($filePath = $this->container->getParameter('xcentric_class_map_path'))) {
            exit("Missing propery xcentric_class_map_path");
        }

        $namespaces = $em->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames();

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

                        if($manyToOne) {
                            $relationship = $manyToOne;
                            $relationType = ClassConfig::RELATION_TYPE_MANY_TO_ONE;
                        }else {
                            $relationship = $manyToMany;
                            $relationType = ClassConfig::RELATION_TYPE_MANY_TO_MANY;
                        }

                        $ns = null;
                        $nsOriginal = $relationship->targetEntity;
                        $nsFromRoot = '\\'.$relationship->targetEntity;
                        if(class_exists($nsOriginal)){
                            $ns = $nsOriginal;
                        }
                        elseif(class_exists($nsFromRoot)){
                            $ns = $nsFromRoot;
                        }

                        if($ns === $className){
                            $classConfig = new ClassConfig();
                            $classConfig->setProperty($property->getName());
                            $classConfig->setClass($reflectionClass->getName());
                            $classConfig->setRelationType($relationType);
                            $classConfig->setOnDeleteType($onDelete->type);

                            $configs[] = $classConfig;
                        }
                    }
                }
            }
        }
        return $configs;
    }

    /**
     * @param $config
     * @return ConfigBuilder
     */
    public function setConfig($config): ConfigBuilder
    {
        $this->config = $config;

        return $this;
    }
}
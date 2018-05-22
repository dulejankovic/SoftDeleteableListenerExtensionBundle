<?php

namespace Xcentric\SoftDeleteableExtensionBundle\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\DependencyInjection\Container;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteMapFileNotFoundException;
use Xcentric\SoftDeleteableExtensionBundle\Exception\OnSoftDeleteUnknownTypeException;
use Xcentric\SoftDeleteableExtensionBundle\Mapping\Annotation\onSoftDelete;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;
use Xcentric\SoftDeleteableExtensionBundle\Service\Config\ConfigBuilder;
use Xcentric\SoftDeleteableExtensionBundle\Service\SoftDeleteProcessor\ProcessByConfig;

/**
 * Soft delete listener class for onSoftDelete behaviour.
 *
 * @author Ruben Harms <info@rubenharms.nl>
 *
 * @link http://www.rubenharms.nl
 * @link https://www.github.com/RubenHarms
 */
class SoftDeleteListener
{
    use ContainerAwareTrait;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws OnSoftDeleteUnknownTypeException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function preSoftDelete(LifecycleEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $entity = $args->getEntity();

        $entityReflection = new \ReflectionObject($entity);

        if($this->container->hasParameter('xcentric_class_map_path')) {
            if (($file = $this->container->getParameter('xcentric_class_map_path')) && file_exists($file)) {

                /** @var ConfigBuilder $configBuilder */
                $configBuilder = $this->container->get('config.builder.factory')->instance(ConfigBuilder::TYPE_YAML);
                $configBuilder->setConfig(file_get_contents($file));
                $configData = $configBuilder->buildByClassName($entityReflection->getName());
            } else {
                throw new OnSoftDeleteMapFileNotFoundException($file);
            }
        } else {
            /** @var ConfigBuilder $configBuilder */
            $configBuilder = $this->container->get('config.builder.factory')->instance(ConfigBuilder::TYPE_ANNOTATION);
            $configData = $configBuilder->buildByClassName($entityReflection->getName());
        }

        if(!isset($configData)) {
            /** @var ProcessByConfig $processor */
            $processor = $this->container->get('on_soft_delete.processor');
            $processor->processEntityByConfig($entity, $configData);
        }
    }
}

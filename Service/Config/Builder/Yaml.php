<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\Config\Builder;

use Xcentric\SoftDeleteableExtensionBundle\Entity\ClassConfig;
use Xcentric\SoftDeleteableExtensionBundle\Service\Config\ConfigBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Yaml implements ConfigBuilder
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
     * @param string $config
     * @return ConfigBuilder
     */
    public function setConfig($config): ConfigBuilder
    {
       $this->config = \Symfony\Component\Yaml\Yaml::parse($config);

       return $this;
    }

    /**
     * @param $className
     * @return array
     */
    public function buildByClassName(string $className): array
    {
        $configurations = [];

        if (empty($this->config) || !isset($this->config[$className])) {
            return $configurations;
        }

        foreach ($this->config[$className] as $property => $value) {
            $cacheConfig = new ClassConfig();

            $cacheConfig->setClass($value['namespace']);
            $cacheConfig->setProperty($property);
            $cacheConfig->setOnDeleteType($value['onDeleteType']);
            $cacheConfig->setRelationType($value['relationType']);

            $configurations[] = $cacheConfig;
        }


        return $configurations;
    }

}
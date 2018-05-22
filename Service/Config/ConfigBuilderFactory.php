<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\Config;

use Xcentric\SoftDeleteableExtensionBundle\Service\Config\ConfigBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigBuilderFactory
 * @package CacheBundle\Service\Config
 */
class ConfigBuilderFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ConfigBuilderFactory constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $type
     * @return ConfigBuilder
     */
    public function instance($type): ConfigBuilder
    {
        switch ($type) {
            case ConfigBuilder::TYPE_YAML:
                return $this->container->get('config.builder.yml');
                break;
            case ConfigBuilder::TYPE_ANNOTATION:
                return $this->container->get('config.builder.annotation');
                break;
            default:
                throw new \RuntimeException("Type {$type} does not exist.");
        }
    }
}
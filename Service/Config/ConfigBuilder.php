<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Service\Config;


use Xcentric\SoftDeleteableExtensionBundle\Entity\ClassConfig;

interface ConfigBuilder
{
    const TYPE_YAML = 'yaml';
    const TYPE_ANNOTATION = 'annotation';

    /**
     * @param string $className
     * @return array
     */
    public function buildByClassName(string $className): array;

    /**
     * @param $config
     * @return ConfigBuilder
     */
    public function setConfig($config): ConfigBuilder;
}
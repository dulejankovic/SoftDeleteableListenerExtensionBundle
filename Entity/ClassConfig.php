<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Entity;


/**
 * Class ClassConfig
 * @package Xcentric\SoftDeleteableExtensionBundle\Entity
 */
class ClassConfig
{
    const RELATION_TYPE_MANY_TO_MANY = 'manyToMany';
    const RELATION_TYPE_MANY_TO_ONE = 'manyToOne';

    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $onDeleteType;

    /**
     * @var string
     */
    protected $relationType;

    /**
     * @return string
     */
    public function getClass(): string
    {
        return (string)$this->class;
    }

    /**
     * @param string $class
     * @return ClassConfig
     */
    public function setClass(?string $class): ClassConfig
    {
        $this->class = (string)$class;
        return $this;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return (string)$this->property;
    }

    /**
     * @param string $property
     * @return ClassConfig
     */
    public function setProperty(?string $property): ClassConfig
    {
        $this->property = (string)$property;
        return $this;
    }

    /**
     * @return string
     */
    public function getOnDeleteType(): string
    {
        return (string)$this->onDeleteType;
    }

    /**
     * @param string $onDeleteType
     * @return ClassConfig
     */
    public function setOnDeleteType(?string $onDeleteType): ClassConfig
    {
        $this->onDeleteType = (string)$onDeleteType;
        return $this;
    }

    /**
     * @return string
     */
    public function getRelationType(): string
    {
        return (string)$this->relationType;
    }

    /**
     * @param string $relationType
     * @return ClassConfig
     */
    public function setRelationType(?string $relationType): ClassConfig
    {
        $this->relationType = (string)$relationType;
        return $this;
    }
}
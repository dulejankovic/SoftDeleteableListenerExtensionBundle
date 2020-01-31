<?php


namespace Xcentric\SoftDeleteableExtensionBundle\Entity;


interface DeletableAware
{
    /**
     * @return bool
     */
    public function isDeletable(): bool;

    /**
     * @param bool|null $isDeletable
     * @return \AppBundle\Entity\Traits\DeletableAware
     */
    public function setIsDeletable(?bool $isDeletable): DeletableAware;
}

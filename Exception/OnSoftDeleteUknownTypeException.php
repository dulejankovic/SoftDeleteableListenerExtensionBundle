<?php

namespace Xcentric\Bundle\SoftDeleteableExtensionBundle\Exception;

class OnSoftDeleteUknownTypeException extends \Exception
{
    public function __construct($type)
    {
        parent::__construct('Type '.$type.' for onSoftDelete annotation does not exists.');
    }
}

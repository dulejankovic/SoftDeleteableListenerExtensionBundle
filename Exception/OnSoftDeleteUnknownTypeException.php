<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Exception;

class OnSoftDeleteUnknownTypeException extends \Exception
{
    public function __construct($type)
    {
        parent::__construct('Type '.$type.' for onSoftDelete annotation does not exists.');
    }
}

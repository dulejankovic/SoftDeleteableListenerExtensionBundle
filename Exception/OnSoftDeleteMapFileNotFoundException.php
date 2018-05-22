<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Exception;

class OnSoftDeleteMapFileNotFoundException extends \Exception
{
    public function __construct($file)
    {
        parent::__construct('File '.$file.' does not exists.');
    }
}

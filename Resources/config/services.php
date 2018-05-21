<?php

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container->setDefinition('xcentric.softdeletale.listener.softdelete', new Definition('Xcentric\SoftDeleteableExtensionBundle\EventListener\SoftDeleteListener', array()))

->addMethodCall('setContainer', array(
    new Reference('service_container'),
))
->addTag('doctrine.event_listener', array(
    'event' => 'preSoftDelete',
));

<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * onSoftDelete annotation for onSoftDelete behavioral extension.
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Ruben Harms <info@rubenharms.nl>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class onSoftDelete extends Annotation
{
    /** @var string @Required */
    public $type;
}

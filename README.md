# SoftDeleteableListenerExtensionBundle

Extensions to Gedmo's softDeleteable listener which has had this issue reported since 2012 : https://github.com/Atlantic18/DoctrineExtensions/issues/505.

Provides the `onSoftDelete` functionality to an association of a doctrine entity. This functionality behaves like the SQL `onDelete` function  (when the owner side is deleted). *It will prevent Doctrine errors when a reference is soft-deleted.*

**Cascade delete the entity**

To (soft-)delete an entity when its parent record is soft-deleted :

```
 @Xcentric\onSoftDelete(type="CASCADE")
```

**Set reference to null (instead of deleting the entity)**

```
 @Xcentric\onSoftDelete(type="SET NULL")
```

## Entity example

``` php
<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Xcentric\SoftDeleteableExtensionBundle\Mapping\Annotation as Xcentric;

/*
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AdvertisementRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Advertisement
{

    ...

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Shop")
     * @ORM\JoinColumn(name="shop_id", referencedColumnName="id")
     * @Xcentric\onSoftDelete(type="CASCADE")
     */
    private $shop;

    ...
}
```

**Optional - Caching class map**


Add property `xcentric_class_map_path`

``` php
# app/config/parameters.yml

    xcentric_class_map_path: '%kernel.root_dir%/config/classMap.yaml'
```

Execute command `xcentric:softdelete:map:generate`

## Install

**Install with composer:**
```
composer require xcentric/soft-deleteable-extension-bundle
```

Add the bundle to `app/AppKernel.php`:

``` php
# app/AppKernel.php

$bundles = array(
    ...
    new Xcentric\SoftDeleteableExtensionBundle\XcentricSoftDeleteableExtensionBundle(),
);
```

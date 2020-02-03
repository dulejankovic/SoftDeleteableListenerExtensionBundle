<?php

namespace Xcentric\SoftDeleteableExtensionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CacheClearCommand
 * @package AppBundle\Command
 */
class GenerateClassMapCommand extends ContainerAwareCommand
{
    const RELATION_TYPE_MANY_TO_MANY = 'manyToMany';
    const RELATION_TYPE_MANY_TO_ONE = 'manyToOne';
    const RELATION_TYPE_ONE_TO_ONE = 'oneToOne';
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('xcentric:softdelete:map:generate')
            ->setDescription('Generate calss map.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $map = array();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        if (!($filePath = $this->getContainer()->getParameter('xcentric_class_map_path'))) {
            exit("Missing propery xcentric_class_map_path");
        }

        $namespaces = $em->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames();

        $reader = new AnnotationReader();
        foreach ($namespaces as $namespace) {
            $reflectionClass = new \ReflectionClass($namespace);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $meta = $em->getClassMetadata($namespace);
            foreach ($reflectionClass->getProperties() as $property) {
                if ($onDelete = $reader->getPropertyAnnotation($property, 'Xcentric\SoftDeleteableExtensionBundle\Mapping\Annotation\onSoftDelete')) {
                    $objects = null;
                    $manyToMany = null;
                    $manyToOne = null;
                    if (($manyToOne = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToOne')) || ($manyToMany = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\ManyToMany'))) {

                        if($manyToOne) {
                            $relationship = $manyToOne;
                            $relationType = self::RELATION_TYPE_MANY_TO_ONE;
                        }else {
                            $relationship = $manyToMany;
                            $relationType = self::RELATION_TYPE_MANY_TO_MANY;
                        }

                        $ns = null;
                        $nsOriginal = $relationship->targetEntity;
                        $nsFromRoot = '\\'.$relationship->targetEntity;
                        if(class_exists($nsOriginal)){
                            $ns = $nsOriginal;
                        }
                        elseif(class_exists($nsFromRoot)){
                            $ns = $nsFromRoot;
                        }

                        $map[$ns][$property->getName()][] = array(
                            "namespace" => $reflectionClass->getName(),
                            "onDeleteType" => $onDelete->type,
                            "relationType" => $relationType
                        );
                    }
                    if ($oneToOne = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\OneToOne')) {

                        $relationship = $oneToOne;
                        $relationType = self::RELATION_TYPE_ONE_TO_ONE;

                        $ns = null;
                        $nsOriginal = $relationship->targetEntity;
                        $nsFromRoot = '\\'.$relationship->targetEntity;
                        if(class_exists($nsOriginal)){
                            $ns = $nsOriginal;
                        }
                        elseif(class_exists($nsFromRoot)){
                            $ns = $nsFromRoot;
                        }

                        $map[$ns][$property->getName()][] = array(
                            "namespace" => $reflectionClass->getName(),
                            "onDeleteType" => $onDelete->type,
                            "relationType" => $relationType
                        );
                    }
                }
            }
        }

        foreach ($namespaces as $namespace) {
            $reflectionClass = new \ReflectionClass($namespace);
            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $parent = $reflectionClass->getParentClass();
            if($parent && isset($map[$parent->getName()])){
                if(!isset($map[$namespace])){
                    $map[$namespace] = [];
                }
                $map[$namespace] = array_merge($map[$namespace], $map[$parent->getName()]);
            }
        }

        $yaml = Yaml::dump($map);

        file_put_contents($filePath, $yaml);
    }
}

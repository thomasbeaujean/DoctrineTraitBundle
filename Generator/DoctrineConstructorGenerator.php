<?php

namespace A5sys\DoctrineTraitBundle\Generator;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineConstructorGenerator
{
    protected static $template =
'
    public function doctrineConstruct()
    {
        <collections>
    }
';

    public function generate(ClassMetadataInfo $metadata): string
    {
        $content = '';

        $collections = [];

        foreach ($metadata->associationMappings as $mapping) {
            if ($mapping['type'] & ClassMetadataInfo::TO_MANY) {
                $collections[] = '$this->'.$mapping['fieldName'].' = new \Doctrine\Common\Collections\ArrayCollection();';
            }
        }

        if (count($collections) > 0) {
            $content = str_replace('<collections>', implode("\n        ", $collections), self::$template);
        }

        return $content;
    }
}

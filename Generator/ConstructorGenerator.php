<?php

namespace A5sys\DoctrineTraitBundle\Generator;

class ConstructorGenerator
{
    protected static $template =
'
    public function __construct()
    {
        $this->doctrineConstruct();
    }
';

    public function generate(): string
    {
        return self::$template;
    }
}

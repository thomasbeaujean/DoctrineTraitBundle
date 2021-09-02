<?php

namespace A5sys\DoctrineTraitBundle\Generator;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

abstract class AbstractPropertyGenerator
{
    protected Inflector $inflector;

    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    protected function convertType(string $type): string
    {
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'integer':
            case 'bigint':
                return 'int';
            case 'decimal':
                return 'float';
            case 'date':
            case 'datetime':
                return '\DateTime';
        }

        return $type;
    }
}

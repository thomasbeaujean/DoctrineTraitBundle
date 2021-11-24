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
            case 'text':
                return 'string';
            case 'boolean':
                return 'bool';
            case 'smallint':
            case 'integer':
            case 'bigint':
                return 'int';
            case 'decimal':
                return 'float';
            case 'json':
            case 'simple_array':
                return 'array';
            case 'date':
            case 'datetime':
                return '\DateTime';
            case 'ulid':
                return '\Symfony\Component\Uid\Ulid';
            case 'uuid':
                return '\Symfony\Component\Uid\Uuid';
        }

        return $type;
    }
}

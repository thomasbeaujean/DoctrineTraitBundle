<?php

namespace A5sys\DoctrineTraitBundle\Generator;

class AddGenerator extends AbstractPropertyGenerator
{
    private static string $template =
    '
    public function <methodName>(<type> $value): void
    {
        $value->set<entityName>($this);
        $this-><fieldName>[] = $value;
    }
';

    public function getMethodName(string $fieldName): string
    {
        $methodName = 'add'.$this->inflector->classify($fieldName);

        return $this->inflector->singularize($methodName);
    }

    public function generate(string $fieldName, string $type, string $entityName): string
    {
        $methodName = $this->getMethodName($fieldName);

        $replacements = [
            '<type>' => $this->convertType($type),
            '<methodName>' => $methodName,
            '<fieldName>' => $fieldName,
            '<entityName>' => ucfirst($entityName)
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$template
        );

        return $method;
    }
}

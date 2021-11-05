<?php

namespace A5sys\DoctrineTraitBundle\Generator;

class AddGenerator extends AbstractPropertyGenerator
{
    private static string $beginTemplate =
    '
    public function <methodName>(<type> $value): void
    {';
    private static string $setTemplate =
    '
        $value->set<entityName>($this);';
    private static string $endTemplate =
    '
        $this-><fieldName>[] = $value;
    }
';

    public function getMethodName(string $fieldName): string
    {
        $methodName = 'add'.$this->inflector->classify($fieldName);

        return $this->inflector->singularize($methodName);
    }

    public function generate(string $fieldName, string $type, ?string $entityName): string
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
            static::$beginTemplate
        );

        if ($entityName) {
            $method .= str_replace(
                array_keys($replacements),
                array_values($replacements),
                static::$setTemplate
            );
        }

        $method .= str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$endTemplate
        );

        return $method;
    }
}

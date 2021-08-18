<?php

namespace A5sys\DoctrineTraitBundle\Generator;

class RemoveGenerator extends AbstractPropertyGenerator
{
    private static string $template =
    '
    public function <methodName>(<type> $value): bool
    {
        return $this-><fieldName>->removeElement($value);
    }
';

    public function getMethodName(string $fieldName): string
    {
        $methodName = 'remove'.$this->inflector->classify($fieldName);

        return $this->inflector->singularize($methodName);
    }

    public function generate(string $fieldName, string $type): string
    {
        $methodName = $this->getMethodName($fieldName);

        $replacements = [
            '<type>' => $this->convertType($type),
            '<methodName>' => $methodName,
            '<fieldName>' => $fieldName,
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$template
        );

        return $method;
    }
}

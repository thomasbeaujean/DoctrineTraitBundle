<?php

namespace A5sys\DoctrineTraitBundle\Generator;

class GetGenerator extends AbstractPropertyGenerator
{
    private static string $template =
    '
    public function <methodName>(): <nullable><type>
    {
        return $this-><fieldName>;
    }
';

    public function getMethodName(string $fieldName): string
    {
        return 'get'.$this->inflector->classify($fieldName);
    }

    public function generate(string $fieldName, string $type, bool $nullable): string
    {
        $methodName = $this->getMethodName($fieldName);

        $replacements = [
            '<type>' => $this->convertType($type),
            '<methodName>' => $methodName,
            '<fieldName>' => $fieldName,
            '<nullable>' => ($nullable ? '?':'')
        ];

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$template
        );

        return $method;
    }
}

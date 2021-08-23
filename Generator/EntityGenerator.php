<?php

namespace A5sys\DoctrineTraitBundle\Generator;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class EntityGenerator
{
    protected static $pathPrefix = 'Traits';
    /** @var mixed[] */
    protected $staticReflection = [];

    const DIRECTORY_SEPARATOR = '/';
    /**
     * The extension to use for written php files.
     *
     * @var string
     */
    protected $extension = '.php';
    protected $spaces = '    ';

    protected static string $classTemplate = '<?php

namespace <namespace>;

<entityClassName>
{
<entityBody>
}
';

    public function generate(array $metadatas, $outputDirectory): void
    {
        foreach ($metadatas as $metadata) {
            $this->writeEntityClass($metadata, $outputDirectory);
        }
    }

    public function generateEntityClass(ClassMetadataInfo $metadata): string
    {
        $placeHolders = [
            '<namespace>',
            '<entityClassName>',
            '<entityBody>',
        ];

        $replacements = [
            $metadata->namespace.'\\Traits',
            $this->generateEntityClassName($metadata),
            $this->generateEntityBody($metadata),
        ];

        $code = str_replace($placeHolders, $replacements, static::$classTemplate);

        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generates and writes entity class to disk for the given ClassMetadataInfo instance.
     *
     * @throws \RuntimeException
     */
    public function writeEntityClass(ClassMetadataInfo $metadata, $outputDirectory): void
    {
        $path = $outputDirectory.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name).$this->extension;
        $this->isNew = !file_exists($path) || (file_exists($path) && $this->regenerateEntityIfExists);

        if (!$this->isNew) {
            $this->parseTokensInEntityFile(file_get_contents($path));
        } else {
            $this->staticReflection[$metadata->name] = array('properties' => array(), 'methods' => array());
        }

        $completeName = $metadata->name;
        $shortName = str_replace('App\\', '', $completeName);
        //$shortName = $completeName;

        $traitPath = $outputDirectory.'/'.str_replace('\\', static::DIRECTORY_SEPARATOR, $shortName).'Trait'.$this->extension;
        $traitPath = str_replace('/Entity/', '/Entity/'.static::$pathPrefix.'/', $traitPath);

        $dir = dirname($traitPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0664, true);
        }

        $content = $this->generateEntityClass($metadata);
        $content = $this->removeTrailingSpacesAndTab($content);
        $cleanedContent = $this->removeUnusefulBlankLineBetweenEndBraces($content);

        file_put_contents($traitPath, $cleanedContent);

        chmod($traitPath, 0664);
    }

    protected function generateEntityClassName(ClassMetadataInfo $metadata): string
    {
        return 'trait '.$this->getClassName($metadata).'Trait';
    }

    protected function hasMethod(string $method, ClassMetadataInfo $metadata): bool
    {
        if (class_exists($metadata->name)) {
            //check that the generated trait is not the one having the method.
            $namespace = str_replace('\\Entity\\', '\\Entity\\'.static::$pathPrefix.'\\', $metadata->namespace);
            $namespaceReplaced = preg_replace("/\\\\Entity$/", '\\Entity\\'.static::$pathPrefix, $namespace);
            $generatedTraitPath = $namespaceReplaced.'\\'.$this->getClassName($metadata).'Trait';

            if (trait_exists($generatedTraitPath)) {
                $traitRc = new \ReflectionClass($generatedTraitPath);
                if ($traitRc->hasMethod($method)) {
                    return false;
                }
            }

            // don't generate method if it is already on the base class.
            $reflClass = new \ReflectionClass($metadata->name);

            if ($reflClass->hasMethod($method)) {
                return true;
            }
        }

        return false;
    }

    protected function generateEntityConstructor(ClassMetadataInfo $metadata): string
    {
        $doctrineConstructorGenerator = new DoctrineConstructorGenerator();
        $doctrineConstructorContent = $doctrineConstructorGenerator->generate($metadata);

        $content = $doctrineConstructorContent;
        if ($doctrineConstructorContent !== '' && !$this->hasMethod('__construct', $metadata)) {
            $content .= "\n";
            $constructorGenerator = new ConstructorGenerator();
            $content .= $constructorGenerator->generate();
        }

        return $content;
    }

    protected function removeTrailingSpacesAndTab($content): string
    {
        $pattern = '/[ ]*\n/';
        $replacement = "\n";
        $cleanedContent = preg_replace($pattern, $replacement, $content);

        return $cleanedContent;
    }

    protected function removeUnusefulBlankLineBetweenEndBraces($content): string
    {
        $pattern = '/}\n\n}/';
        $replacement = "}\n}";
        $cleanedContent = preg_replace($pattern, $replacement, $content);

        return $cleanedContent;
    }

    protected function generateEntityStubMethods(ClassMetadataInfo $metadata): string
    {
        $methods = [];

        $getGenerator = new GetGenerator();
        $setGenerator = new SetGenerator();

        $addGenerator = new AddGenerator();
        $removeGenerator = new RemoveGenerator();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['declaredField'], $metadata->embeddedClasses[$fieldMapping['declaredField']])) {
                continue;
            }

            $reflection = new \ReflectionProperty($metadata->name, $fieldMapping['fieldName']);
            $isNullableField = false;
            if ($reflection->getType()) {
                $isNullableField = $reflection->getType()->allowsNull();
            }

            if (!$this->hasMethod($setGenerator->getMethodName($fieldMapping['fieldName']), $metadata)) {
                $methods[] = $setGenerator->generate($fieldMapping['fieldName'], $fieldMapping['type'], $isNullableField);
            }
            if (!$this->hasMethod($getGenerator->getMethodName($fieldMapping['fieldName']), $metadata)) {
                $methods[] = $getGenerator->generate($fieldMapping['fieldName'], $fieldMapping['type'], $isNullableField);
            }
        }

        foreach ($metadata->embeddedClasses as $fieldName => $embeddedClass) {

            if (isset($embeddedClass['declaredField'])) {
                continue;
            }
            throw new \LogicException('embeddedClasses not handled yet');
        }

        foreach ($metadata->associationMappings as $associationMapping) {
            if ($associationMapping['type'] & ClassMetadataInfo::TO_ONE) {
                $reflection = new \ReflectionProperty($metadata->name, $associationMapping['fieldName']);
                $isNullableField = false;
                if ($reflection->getType()) {
                     $isNullableField = $reflection->getType()->allowsNull();
                }
                if (!$this->hasMethod($setGenerator->getMethodName($associationMapping['fieldName']), $metadata)) {
                    $methods[] = $setGenerator->generate($associationMapping['fieldName'], '\\'.$associationMapping['targetEntity'], $isNullableField);
                }
                if (!$this->hasMethod($getGenerator->getMethodName($associationMapping['fieldName']), $metadata)) {
                    $methods[] = $getGenerator->generate($associationMapping['fieldName'], '\\'.$associationMapping['targetEntity'], $isNullableField);
                }
            } elseif ($associationMapping['type'] & ClassMetadataInfo::TO_MANY) {
                if (!$this->hasMethod($addGenerator->getMethodName($associationMapping['fieldName']), $metadata)) {
                    $methods[] = $addGenerator->generate($associationMapping['fieldName'], '\\'.$associationMapping['targetEntity'], $associationMapping['mappedBy']);
                }
                if (!$this->hasMethod($removeGenerator->getMethodName($associationMapping['fieldName']), $metadata)) {
                    $methods[] = $removeGenerator->generate($associationMapping['fieldName'], '\\'.$associationMapping['targetEntity']);
                }
                if (!$this->hasMethod($getGenerator->getMethodName($associationMapping['fieldName']), $metadata)) {
                    $methods[] = $getGenerator->generate($associationMapping['fieldName'], '\Doctrine\ORM\PersistentCollection', false);
                }
            }
        }

        return implode("\n\n", array_filter($methods));
    }

    protected function generateEntityBody(ClassMetadataInfo $metadata): string
    {
        $stubMethods = $this->generateEntityStubMethods($metadata);
        $code = [];
        $code[] = $this->generateEntityConstructor($metadata);

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        return implode("\n", $code);
    }

        /**
     * @param string $src
     *
     * @return void
     *
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     * @psalm-suppress UndefinedConstant
     */
    protected function parseTokensInEntityFile($src)
    {
        $tokens            = token_get_all($src);
        $tokensCount       = count($tokens);
        $lastSeenNamespace = '';
        $lastSeenClass     = false;

        $inNamespace = false;
        $inClass     = false;

        for ($i = 0; $i < $tokensCount; $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($inNamespace) {
                if (in_array($token[0], [T_NS_SEPARATOR, T_STRING], true)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (PHP_VERSION_ID >= 80000 && ($token[0] === T_NAME_QUALIFIED || $token[0] === T_NAME_FULLY_QUALIFIED)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, [';', '{'], true)) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass                                              = false;
                $lastSeenClass                                        = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = [];
                $this->staticReflection[$lastSeenClass]['methods']    = [];
            }

            if ($token[0] === T_NAMESPACE) {
                $lastSeenNamespace = '';
                $inNamespace       = true;
            } elseif ($token[0] === T_CLASS && $tokens[$i - 1][0] !== T_DOUBLE_COLON) {
                $inClass = true;
            } elseif ($token[0] === T_FUNCTION) {
                if ($tokens[$i + 2][0] === T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i + 2][1]);
                } elseif ($tokens[$i + 2] === '&' && $tokens[$i + 3][0] === T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i + 3][1]);
                }
            } elseif (in_array($token[0], [T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED], true) && $tokens[$i + 2][0] !== T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i + 2][1], 1);
            }
        }
    }

    protected function getClassName(ClassMetadataInfo $metadata): string
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }
}

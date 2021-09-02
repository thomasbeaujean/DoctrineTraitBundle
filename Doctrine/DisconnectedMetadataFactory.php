<?php

namespace A5sys\DoctrineTraitBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Mapping\ClassMetadataCollection;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory as MappingDisconnectedMetadataFactory;
use RuntimeException;

use function array_pop;
use function explode;
use function implode;
use function sprintf;

/**
 * This class provides methods to access Doctrine entity class metadata for a
 * given bundle, namespace or entity class, for generation purposes
 */
class DisconnectedMetadataFactory extends MappingDisconnectedMetadataFactory
{
    /**
     * Find and configure path and namespace for the metadata collection.
     *
     * @param string|null $path
     *
     * @throws RuntimeException When unable to determine the path.
     */
    public function findNamespaceAndPathForMetadata(ClassMetadataCollection $metadata, $path = null)
    {
        $all = $metadata->getMetadata();

        if ($path) {
            // Get namespace by removing the last component of the FQCN
            $nsParts = explode('\\', $all[0]->name);
            array_pop($nsParts);
            $ns = implode('\\', $nsParts);
        } else {
            throw new RuntimeException(sprintf('Unable to determine where to save the "%s" class (use the --path option).', $all[0]->name));
        }

        $metadata->setPath($path);
        $metadata->setNamespace($ns);
    }
}

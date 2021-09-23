<?php
namespace ExtractMetadata\Extractor;

/**
 * Interface for metadata extractors.
 */
interface ExtractorInterface
{
    /**
     * Is this extractor available?
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Extract metadata from a file.
     *
     * Returns the extracted metadata of the file formatted as an array, keyed
     * by tag name.
     *
     * @param string $filePath The path to a file
     * @param string $metadataType The type of metadata
     * @return array
     */
    public function extract($filePath, $metadataType);
}

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
     * Returns the extracted metadata of the file or false if the extractor
     * could not extract metadata.
     *
     * @param string $filePath The path to a file
     * @param string $metadataType
     * @return string|false
     */
    public function extract($filePath, $metadataType);
}

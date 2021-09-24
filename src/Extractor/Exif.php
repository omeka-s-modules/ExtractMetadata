<?php
namespace ExtractMetadata\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use PHP's exif library to extract text.
 *
 * @see https://www.php.net/manual/en/book.exif.php
 */
class Exif implements ExtractorInterface
{
    public function isAvailable()
    {
        return extension_loaded('exif');
    }

    public function extract($filePath, $metadataType)
    {
        // @see https://www.php.net/manual/en/function.exif-read-data.php
        return exif_read_data($filePath);
    }
}

<?php
namespace ExtractMetadata\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use exiftool to extract text.
 *
 * @see https://exiftool.org/exiftool_pod.html
 */
class Exiftool implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('exiftool');
    }

    public function extract($filePath, $metadataType)
    {
        $commandPath = $this->cli->getCommandPath('exiftool');
        if (false === $commandPath) {
            return false;
        }
        // Use options that maximize machine-readability.
        //   -tab: Output a tab-delimited list of description/values
        //   -short: Short output format. Prints tag names instead of descriptions
        //   -struct: Output structured XMP information instead of flattening to individual tags.
        $commandArgs = [$commandPath, '-tab -short -struct'];
        switch ($metadataType) {
            case 'exif':
                $commandArgs[] = '-exif:all';
                break;
            case 'iptc':
                $commandArgs[] = '-iptc:all';
                break;
            case 'xmp':
                $commandArgs[] = '-xmp:all';
                break;
            case 'pdf':
                $commandArgs[] = '-pdf:all';
                break;
            case 'photoshop':
                $commandArgs[] = '-photoshop:all';
                break;
            case 'gif':
                $commandArgs[] = '-gif:all';
                break;
            case 'iccprofile':
                $commandArgs[] = '-icc_profile:all';
                break;
            case 'png':
                $commandArgs[] = '-png:all';
                break;
            case 'app14':
                $commandArgs[] = '-app14:all';
                break;
            case 'riff':
                $commandArgs[] = '-riff:all';
                break;
            case 'mpeg':
                $commandArgs[] = '-mpeg:all';
                break;
            case 'id3':
                $commandArgs[] = '-id3:all';
                break;
            case 'svg':
                $commandArgs[] = '-svg:all';
                break;
            default:
                // This extractor does not support this metadata type.
                return false;
        }
        $commandArgs[] = $filePath;
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}

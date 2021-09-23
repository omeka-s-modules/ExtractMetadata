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
        //   -json: Output a JSON list of descriptions/values
        $commandArgs = [$commandPath, '-json'];
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
            case 'icc_profile':
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
            case 'quicktime':
                $commandArgs[] = '-quicktime:all';
                break;
            case 'vorbis':
                $commandArgs[] = '-vorbis:all';
                break;
            case 'asf':
                $commandArgs[] = '-asf:all';
                break;
            case 'flashpix':
                $commandArgs[] = '-flashpix:all';
                break;
            case 'zip':
                $commandArgs[] = '-zip:all';
                break;
            case 'xml':
                $commandArgs[] = '-xml:all';
                break;
            case 'rtf':
                $commandArgs[] = '-rtf:all';
                break;
            case 'aiff':
                $commandArgs[] = '-aiff:all';
                break;
            case 'flac':
                $commandArgs[] = '-flac:all';
                break;
            case 'zip':
                $commandArgs[] = '-zip:all';
                break;
            case 'exe':
                $commandArgs[] = '-exe:all';
                break;
            case 'theora':
                $commandArgs[] = '-theora:all';
                break;
            case 'opus':
                $commandArgs[] = '-opus:all';
                break;
            case 'flash':
                $commandArgs[] = '-flash:all';
                break;
            default:
                // This extractor does not support this metadata type.
                return false;
        }
        $commandArgs[] = $filePath;
        $command = implode(' ', $commandArgs);
        $metadata = json_decode($this->cli->execute($command), true)[0];
        // "SourceFile" added by exiftool. Remove it.
        unset($metadata['SourceFile']);
        return $metadata;
    }
}

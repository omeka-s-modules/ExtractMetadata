<?php
namespace ExtractMetadata;

use Omeka\Entity;
use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    const VOCAB_NAMESPACE_URI = 'http://omeka.org/s/vocabs/o-module-extractmetadata#';
    const VOCAB_PREFIX = 'extractmetadata';
    const VOCAB_LABEL = 'Extract Metadata';
    const VOCAB_PROPERTIES = [
        'exif' => [
            'label' => 'Exif',
            'comment' => 'Exchangeable Image File',
        ],
        'iptciim' => [
            'label' => 'IPTC IIM',
            'comment' => 'IPTC Information Interchange Model',
        ],
        'xmp' => [
            'label' => 'XMP',
            'comment' => 'Extensible Metadata Platform',
        ],
    ];

    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $this->importVocab();
    }

    public function getConfigForm(PhpRenderer $view)
    {
        $extractors = $this->getServiceLocator()->get('ExtractMetadata\ExtractorManager');
        $html = '
        <table class="tablesaw tablesaw-stack">
            <thead>
            <tr>
                <th>' . $view->translate('Extractor') . '</th>
                <th>' . $view->translate('Available') . '</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($extractors->getRegisteredNames() as $extractorName) {
            $extractor = $extractors->get($extractorName);
            $isAvailable = $extractor->isAvailable()
                ? sprintf('<span style="color: green;">%s</span>', $view->translate('Yes'))
                : sprintf('<span style="color: red;">%s</span>', $view->translate('No'));
            $html .= sprintf('
            <tr>
                <td>%s</td>
                <td>%s</td>
            </tr>', $extractorName, $isAvailable);
        }
        $html .= '
            </tbody>
        </table>';
        return $html;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /*
         * Before ingesting a media file, extract its metadata and set it to the
         * media. This will only happen when creating the media.
         */
        $sharedEventManager->attach(
            '*',
            'media.ingest_file.pre',
            function (Event $event) {
                $tempFile = $event->getParam('tempFile');
                $this->setMetadataToMedia(
                    $tempFile->getTempPath(),
                    $event->getTarget(),
                    $tempFile->getMediaType()
                );
            }
        );
    }

    /**
     * Import the "Extract Metadata" vocabulary.
     *
     * This will import the vocabulary and its properties if they are not
     * already imported. Use this method during upgrade if adding new
     * properties.
     */
    protected function importVocab()
    {
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $vocab = $em->getRepository(Entity\Vocabulary::class)
            ->findOneBy(['namespaceUri' => self::VOCAB_NAMESPACE_URI]);
        if (!$vocab) {
            $vocab = new Entity\Vocabulary;
            $vocab->setNamespaceUri(self::VOCAB_NAMESPACE_URI);
            $vocab->setPrefix(self::VOCAB_PREFIX);
            $vocab->setLabel(self::VOCAB_LABEL);
            $em->persist($vocab);
        }
        foreach (self::VOCAB_PROPERTIES as $localName => $propertyData) {
            $property = $em->getRepository(Entity\Property::class)
                ->findOneBy([
                    'vocabulary' => $vocab,
                    'localName' => $localName,
                ]);
            if (!$property) {
                $property = new Entity\Property;
                $property->setVocabulary($vocab);
                $property->setLocalName($localName);
                $property->setLabel($propertyData['label']);
                $property->setComment($propertyData['comment']);
                $em->persist($property);
            }
        }
        $em->flush();
    }

    /**
     * Set extracted metadata to a media.
     *
     * @param string $filePath
     * @param Media $media
     * @param string $mediaType
     * @return null|false
     */
    public function setMetadataToMedia($filePath, Entity\Media $media, $mediaType = null)
    {
        if (!@is_file($filePath)) {
            // The file doesn't exist.
            return;
        }
        if (null === $mediaType) {
            // Fall back on the media type set to the media.
            $mediaType = $media->getMediaType();
        }
        $config = $this->getServiceLocator()->get('Config');
        if (!isset($config['extract_metadata_media_type_map'][$mediaType])) {
            // The media type has no associated extractors.
            return;
        }
        $extractors = $this->getServiceLocator()->get('ExtractMetadata\ExtractorManager');
        foreach ($config['extract_metadata_media_type_map'][$mediaType] as $metadataType => $extractorName) {
            try {
                $extractor = $extractors->get($extractorName);
            } catch (ServiceNotFoundException $e) {
                // The extractor cannot be found.
                continue;
            }
            if (!$extractor->isAvailable()) {
                // The extractor is unavailable.
                continue;
            }
            // extract() should return false if it cannot extract metadata.
            $metadata = $extractor->extract($filePath, $metadataType);
            if (false === $metadata) {
                // The extractor could not extract metadata from the file.
                continue;
            }
            $metadata = trim($metadata);
            // @todo Save metadata to corresponding property
        }
    }
}

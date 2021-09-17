<?php
namespace ExtractMetadata;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
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
        'iptc' => [
            'label' => 'IPTC IIM',
            'comment' => 'International Press Telecommunications Council Information Interchange Model',
        ],
        'xmp' => [
            'label' => 'XMP',
            'comment' => 'Extensible Metadata Platform',
        ],
        'pdf' => [
            'label' => 'PDF',
            'comment' => 'Portable Document Format',
        ],
        'photoshop' => [
            'label' => 'Photoshop IRB',
            'comment' => null,
        ],
        'gif' => [
            'label' => 'GIF',
            'comment' => 'Graphics Interchange Format',
        ],
        'iccprofile' => [
            'label' => 'ICC Profile',
            'comment' => 'International Color Consortium profile',
        ],
        'png' => [
            'label' => 'PNG',
            'comment' => 'Portable Network Graphics',
        ],
        'app14' => [
            'label' => 'APP14',
            'comment' => null,
        ],
        'riff' => [
            'label' => 'RIFF',
            'comment' => 'Resource Interchange File Format',
        ],
        'mpeg' => [
            'label' => 'MPEG',
            'comment' => 'Moving Picture Experts Group',
        ],
        'id3' => [
            'label' => 'ID3',
            'comment' => null,
        ],
        'svg' => [
            'label' => 'SVG',
            'comment' => 'Scalable Vector Graphics',
        ],
        'quicktime' => [
            'label' => 'QuickTime',
            'comment' => null,
        ],
    ];

    /**
     * Metadata type properties cache
     *
     * @var array
     */
    protected $metadataTypeProperties;

    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $this->importVocab($services->get('Omeka\EntityManager'));
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
     *
     * @param EntityManager
     */
    protected function importVocab(EntityManager $entityManager)
    {
        $vocab = $entityManager->getRepository(Entity\Vocabulary::class)
            ->findOneBy(['namespaceUri' => self::VOCAB_NAMESPACE_URI]);
        if (!$vocab) {
            $vocab = new Entity\Vocabulary;
            $vocab->setNamespaceUri(self::VOCAB_NAMESPACE_URI);
            $vocab->setPrefix(self::VOCAB_PREFIX);
            $vocab->setLabel(self::VOCAB_LABEL);
            $entityManager->persist($vocab);
        }
        foreach (self::VOCAB_PROPERTIES as $localName => $propertyData) {
            $property = $entityManager->getRepository(Entity\Property::class)
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
                $entityManager->persist($property);
            }
        }
        $entityManager->flush();
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
        if (null === $mediaType) {
            // Fall back on the media type set to the media.
            $mediaType = $media->getMediaType();
        }
        if (!@is_file($filePath)) {
            // The file doesn't exist.
            return;
        }
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        if (!isset($config['extract_metadata_media_types'][$mediaType])) {
            // The media type has no associated extractors.
            return;
        }
        $extractors = $services->get('ExtractMetadata\ExtractorManager');
        $entityManager = $services->get('Omeka\EntityManager');
        $metadataTypeProperties = $this->getMetadataTypeProperties();
        $mediaValues = $media->getValues();
        // Iterate each metadata type, extract the metadata using the extractor,
        // and set the metadata as value(s) of the media.
        foreach ($config['extract_metadata_media_types'][$mediaType] as $metadataType => $extractorName) {
            if (!isset($this->metadataTypeProperties[$metadataType])) {
                // This metadata type does not have a corresponding property.
                return;
            }
            $metadataTypeProperty = $this->metadataTypeProperties[$metadataType];
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
            if ('' === $metadata) {
                // The extractor returned an empty string.
                continue;
            }

            $isPublic = true;
            // Clear values.
            $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $metadataTypeProperty));
            foreach ($mediaValues->matching($criteria) as $mediaValueMetadataTypeProperty) {
                $isPublic = $mediaValueMetadataTypeProperty->getIsPublic();
                $mediaValues->removeElement($mediaValueMetadataTypeProperty);
            }
            // Use a property reference to avoid Doctrine's "A new entity was
            // found" error during batch operations.
            $metadataTypeProperty = $entityManager->getReference(Entity\Property::class, $metadataTypeProperty->getId());
            // Create and add the value.
            $value = new Entity\Value;
            $value->setResource($media);
            $value->setType('literal');
            $value->setProperty($metadataTypeProperty);
            $value->setValue($metadata);
            $value->setIsPublic($isPublic);
            $mediaValues->add($value);
        }
    }

    /**
     * Get all properties of the "Extract Metadata" vocabulary.
     *
     * @return array An array of properties keyed by their local name
     */
    public function getMetadataTypeProperties()
    {
        if (isset($this->metadataTypeProperties)) {
            return $this->metadataTypeProperties;
        }
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $vocab = $entityManager->getRepository(Entity\Vocabulary::class)
            ->findOneBy(['namespaceUri' => self::VOCAB_NAMESPACE_URI]);
        if (!$vocab) {
            // The "Extract Metadata" vocabulary was deleted. Re-import it.
            return [];
        }
        $this->metadataTypeProperties = [];
        foreach ($vocab->getProperties() as $property) {
            $this->metadataTypeProperties[$property->getLocalName()] = $property;
        }
        return $this->metadataTypeProperties;
    }
}

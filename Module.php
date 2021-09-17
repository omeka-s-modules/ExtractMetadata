<?php
namespace ExtractMetadata;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Omeka\Entity;
use Omeka\File\Store\Local;
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
        /**
         * After hydrating a media, perform the requested extract_metadata_action.
         *
         * There are two actions this method can perform:
         *
         * - refresh: (re)extracts metadata from files
         * - clear: clears all extracted metadata media
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $media = $event->getParam('entity');
                $data = $event->getParam('request')->getContent();
                $action = $data['extract_metadata_action'] ?? 'default';
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                // Files must be stored locally to refresh extracted metadata.
                if (('refresh' === $action) && ($store instanceof Local)) {
                    $filePath = $store->getLocalPath(sprintf('original/%s', $media->getFilename()));
                    $this->setMetadataToMedia($filePath, $media, $media->getMediaType());
                } elseif ('clear' === $action) {
                    $mediaValues = $media->getValues();
                    foreach ($this->getMetadataTypeProperties() as $metadataTypeProperty) {
                        $criteria = Criteria::create()
                            ->where(Criteria::expr()->eq('property', $metadataTypeProperty))
                            ->andWhere(Criteria::expr()->eq('type', 'literal'));
                        foreach ($mediaValues->matching($criteria) as $mediaValue) {
                            $mediaValues->removeElement($mediaValue);
                        }
                    }
                }
            }
        );
        /*
         * Add the ExtractMetadata control to the media batch update form.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if ('media' !== $resourceType) {
                    // This is not a media batch update form.
                    return;
                }
                $valueOptions = [
                    'clear' => 'Clear metadata', // @translate
                    '' => '[No action]', // @translate
                ];
                $store = $this->getServiceLocator()->get('Omeka\File\Store');
                if ($store instanceof Local) {
                    // Files must be stored locally to refresh extracted metadata.
                    $valueOptions = ['refresh' => 'Refresh metadata'] + $valueOptions; // @translate
                }
                $form->add([
                    'name' => 'extract_metadata_action',
                    'type' => 'Laminas\Form\Element\Radio',
                    'options' => [
                        'label' => 'Extract metadata', // @translate
                        'value_options' => $valueOptions,
                    ],
                    'attributes' => [
                        'value' => '',
                        'data-collection-action' => 'replace',
                    ],
                ]);
            }
        );
        /*
         * Don't require the ExtractMetadata control in the media batch update
         * form.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_input_filters',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if ('media' !== $resourceType) {
                    // This is not a media batch update form.
                    return;
                }
                $inputFilter = $event->getParam('inputFilter');
                $inputFilter->add([
                    'name' => 'extract_metadata_action',
                    'required' => false,
                ]);
            }
        );
        /*
         * When preprocessing the batch update data, authorize the "extract_
         * metadata_action" key. This will signal the process to refresh or
         * clear the metadata while updating each media in the batch.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $adapter = $event->getTarget();
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if (isset($rawData['extract_metadata_action'])
                    && in_array($rawData['extract_metadata_action'], ['refresh', 'clear'])
                ) {
                    $data['extract_metadata_action'] = $rawData['extract_metadata_action'];
                }
                $event->setParam('data', $data);
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
            // The vocabulary doesn't already exist. Create it.
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
                // The property doesn't already exist. Create it.
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
    public function setMetadataToMedia($filePath, Entity\Media $media, $mediaType)
    {
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
            foreach ($mediaValues->matching($criteria) as $mediaValue) {
                $isPublic = $mediaValue->getIsPublic();
                $mediaValues->removeElement($mediaValue);
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

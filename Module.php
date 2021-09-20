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
    /**
     * "Extract Metadata" vocabulary properties, keyed by local name. Every
     * local name is also a metadata type.
     */
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
        'vorbis' => [
            'label' => 'Vorbis',
            'comment' => null,
        ],
        'asf' => [
            'label' => 'ASF',
            'comment' => 'Advanced Systems Format',
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
        return array_merge(
            include sprintf('%s/config/module.config.php', __DIR__),
            include sprintf('%s/config/media_types.config.php', __DIR__),
        );
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
        /*
         * After hydrating a media, perform the requested extract_metadata_action.
         * This will only happen when updating the media.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $request = $event->getParam('request');
                if ('update' !== $request->getOperation()) {
                    // This is not an update operation.
                    return;
                }
                $media = $event->getParam('entity');
                $data = $request->getContent();
                $action = $data['extract_metadata_action'] ?? 'default';
                $this->performActionOnMedia($media, $action);
            }
        );
        /*
         * After hydrating an item, perform the requested extract_metadata_action.
         * This will only happen when updating the item.
         */
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            function (Event $event) {
                $request = $event->getParam('request');
                if ('update' !== $request->getOperation()) {
                    // This is not an update operation.
                    return;
                }
                $item = $event->getParam('entity');
                $data = $request->getContent();
                $action = $data['extract_metadata_action'] ?? 'default';
                foreach ($item->getMedia() as $media) {
                    $this->performActionOnMedia($media, $action);
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
                if (!in_array($resourceType, ['media', 'item'])) {
                    // This is not a media or item batch update form.
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
         * Don't require the ExtractMetadata control in the batch update forms.
         */
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_input_filters',
            function (Event $event) {
                $form = $event->getTarget();
                $resourceType = $form->getOption('resource_type');
                if (!in_array($resourceType, ['media', 'item'])) {
                    // This is not a media or item batch update form.
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
         * Authorize the "extract_metadata_action" key when preprocessing the
         * batch update data. This will signal the process to refresh or clear
         * the metadata while updating each resource in the batch.
         */
        $preprocessBatchUpdate = function (Event $event) {
            $adapter = $event->getTarget();
            $data = $event->getParam('data');
            $rawData = $event->getParam('request')->getContent();
            if (isset($rawData['extract_metadata_action'])
                && in_array($rawData['extract_metadata_action'], ['refresh', 'clear'])
            ) {
                $data['extract_metadata_action'] = $rawData['extract_metadata_action'];
            }
            $event->setParam('data', $data);
        };
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\MediaAdapter',
            'api.preprocess_batch_update',
            $preprocessBatchUpdate
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            $preprocessBatchUpdate
        );
        /*
         * Add an "Extract metadata" tab to the item and media edit pages.
         */
        $viewEditSectionNav = function (Event $event) {
            $view = $event->getTarget();
            $sectionNavs = $event->getParam('section_nav');
            $sectionNavs['extract-metadata'] = $view->translate('Extract metadata');
            $event->setParam('section_nav', $sectionNavs);
        };
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            $viewEditSectionNav
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.section_nav',
            $viewEditSectionNav
        );
        /*
         * Add an "Extract metadata" section to the item and media edit pages.
         */
        $viewEditFormAfter = function (Event $event) {
            $view = $event->getTarget();
            $store = $this->getServiceLocator()->get('Omeka\File\Store');
            $refreshRadioButton = null;
            if ($store instanceof Local) {
                // Files must be stored locally to refresh extracted text.
                $refreshRadioButton = sprintf(
                    '<label><input type="radio" name="extract_metadata_action" value="refresh">%s</label>',
                    $view->translate('Refresh metadata')
                );
            }
            $html = sprintf('
            <div id="extract-metadata" class="section">
                <div class="field">
                    <div class="field-meta">
                        <label for="extract_metadata_action">%s</label>
                    </div>
                    <div class="inputs">
                        %s
                        <label><input type="radio" name="extract_metadata_action" value="clear">%s</label>
                        <label><input type="radio" name="extract_metadata_action" value="" checked="checked">%s</label>
                    </div>
                </div>
            </div>',
            $view->translate('Extract metadata'),
            $refreshRadioButton,
            $view->translate('Clear metadata'),
            $view->translate('[No action]'));
            echo $html;
        };
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            $viewEditFormAfter
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.edit.form.after',
            $viewEditFormAfter
        );
    }

    /**
     * Import the "Extract Metadata" vocabulary.
     *
     * This will import the vocabulary and its properties if they are not
     * already imported. Use this method during upgrade if adding new
     * properties. Simply add the properties to self::VOCAB_PROPERTIES and call
     * this method.
     *
     * @param EntityManager $entityManager
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
     * @param Entity\Media $media
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
     * Perform an extract metadata action on a media. There are two actions this
     * method can perform:
     *
     * - refresh: (re)extracts metadata from files and sets them to the media
     * - clear: clears all extracted metadata from the media
     *
     * @param Entity\Media $media
     * @param string $action
     */
    public function performActionOnMedia(Entity\Media $media, $action)
    {
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
            // The "Extract Metadata" vocabulary was deleted.
            return [];
        }
        $this->metadataTypeProperties = [];
        foreach ($vocab->getProperties() as $property) {
            $this->metadataTypeProperties[$property->getLocalName()] = $property;
        }
        return $this->metadataTypeProperties;
    }
}

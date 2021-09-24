<?php
namespace ExtractMetadata;

use DateTime;
use Doctrine\Common\Collections\Criteria;
use ExtractMetadata\Entity\ExtractMetadata;
use Omeka\Entity;
use Omeka\File\Store\Local;
use Omeka\Module\AbstractModule;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    const ACTIONS = [
        'refresh' => 'Refresh metadata', // @translate
        'refresh_map_add' => 'Refresh and map metadata (add values)', // @translate
        'refresh_map_replace' => 'Refresh and map metadata (replace values)', // @translate
        'map_add' => 'Map metadata (add values)', // @translate
        'map_replace' => 'Map metadata (replace values)', // @translate
    ];

    public function getConfig()
    {
        return array_merge(
            include sprintf('%s/config/module.config.php', __DIR__),
            include sprintf('%s/config/extract.config.php', __DIR__),
            include sprintf('%s/config/crosswalk.config.php', __DIR__)
        );
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE extract_metadata (id INT UNSIGNED AUTO_INCREMENT NOT NULL, media_id INT NOT NULL, extracted DATETIME NOT NULL, metadata LONGTEXT NOT NULL COMMENT '(DC2Type:json)', UNIQUE INDEX UNIQ_4DA36818EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE extract_metadata ADD CONSTRAINT FK_4DA36818EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE CASCADE;
SQL;
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec($sql);
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS extract_metadata;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function getConfigForm(PhpRenderer $view)
    {
        $services = $this->getServiceLocator();
        $extractors = $services->get('ExtractMetadata\ExtractorManager');
        $config = $services->get('Config');
        $mediaTypes = $config['extract_metadata_media_types'];
        ksort($mediaTypes);
        return $view->partial('common/extract-metadata-config-form', [
            'extractors' => $extractors,
            'mediaTypes' => $mediaTypes,
        ]);
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /*
         * Extract and map metadata before ingesting media file. This will only
         * happen when creating the media.
         */
        $sharedEventManager->attach(
            '*',
            'media.ingest_file.pre',
            function (Event $event) {
                $media = $event->getTarget();
                $tempFile = $event->getParam('tempFile');
                $metadataEntity = $this->extractMetadata(
                    $tempFile->getTempPath(),
                    $tempFile->getMediaType(),
                    $media
                );
                if ($metadataEntity) {
                    $this->mapMetadata($media, $metadataEntity->getMetadata());
                }
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
                $this->performAction($media, $action);
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
                    $this->performAction($media, $action);
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
                $element = $this->getActionSelect();
                $element->setAttribute('data-collection-action', 'replace');
                $form->add($element);
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
         * batch update data. This will signal the process to refresh or map the
         * metadata while updating each resource in the batch.
         */
        $preprocessBatchUpdate = function (Event $event) {
            $adapter = $event->getTarget();
            $data = $event->getParam('data');
            $rawData = $event->getParam('request')->getContent();
            if (isset($rawData['extract_metadata_action'])
                && in_array($rawData['extract_metadata_action'], array_keys(self::ACTIONS))
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
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.section_nav',
            $viewEditSectionNav
        );
        /*
         * Add an "Extract metadata" section to the item and media edit pages.
         */
        $viewEditFormAfter = function (Event $event) {
            $view = $event->getTarget();
            $store = $this->getServiceLocator()->get('Omeka\File\Store');
            $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
            // Set the form element, if needed.
            $element = null;
            if ('view.show.after' !== $event->getName()) {
                $element = $this->getActionSelect();
            }
            // Set the metadata entity, if needed.
            $metadataEntity = null;
            if ($view->media) {
                $metadataEntity = $entityManager->getRepository(ExtractMetadata::class)
                    ->findOneBy(['media' => $view->media->id()]);
            }
            echo $view->partial('common/extract-metadata-section', [
                'element' => $element,
                'metadataEntity' => $metadataEntity,
            ]);
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
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Media',
            'view.show.after',
            $viewEditFormAfter
        );
    }

    /**
     * Extracted metadata.
     *
     * @param string $filePath
     * @param string $mediaType
     * @param Entity\Media $media
     * @return null|ExtractMetadata
     */
    public function extractMetadata($filePath, $mediaType, Entity\Media $media)
    {
        if (!@is_file($filePath)) {
            // The file doesn't exist.
            return;
        }
        $config = $this->getServiceLocator()->get('Config');
        if (!isset($config['extract_metadata_extract'][$mediaType])) {
            // The media type has no associated extractors.
            return;
        }
        $extractors = $this->getServiceLocator()->get('ExtractMetadata\ExtractorManager');
        // Iterate each metadata type, extract the metadata using the extractor.
        $metadata = [];
        foreach ($config['extract_metadata_extract'][$mediaType] as $metadataType => $extractorName) {
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
            $typeMetadata = $extractor->extract($filePath, $metadataType);
            if (!is_array($typeMetadata)) {
                // The extractor did not return an array.
                continue;
            }
            $metadata[$metadataType] = $typeMetadata;
        }
        // Create the metadata entity.
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $metadataEntity = $entityManager->getRepository(ExtractMetadata::class)->findOneBy(['media' => $media]);
        if (!$metadataEntity) {
            $metadataEntity = new ExtractMetadata;
            $metadataEntity->setExtracted(new DateTime('now'));
            $metadataEntity->setMedia($media);
            $entityManager->persist($metadataEntity);
        }
        $metadataEntity->setMetadata($metadata);
        return $metadataEntity;
    }

    /**
     * Map metadata using crosswalk.
     *
     * @param Entity\Media $media
     * @param array $metadata
     * @param bool $replace
     */
    public function mapMetadata(Entity\Media $media, array $metadata, $replace = false)
    {
        $config = $this->getServiceLocator()->get('Config');
        $mediaValues = $media->getValues();
        $mappedValues = [];
        foreach ($config['extract_metadata_crosswalk'] as $metadataType => $crosswalkData) {
            if (!isset($metadata[$metadataType])) {
                // The metadata does not include this metadata type.
                continue;
            }
            foreach ($crosswalkData as $tagName => $term) {
                if (!isset($metadata[$metadataType][$tagName])) {
                    // The metadata does not include this tag name.
                    continue;
                }
                $property = $this->getPropertyByTerm($term);
                if (!$property) {
                    // A property does not exist with this term.
                    continue;
                }
                if ($replace) {
                    // Remove existing values.
                    $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $property));
                    foreach ($mediaValues->matching($criteria) as $mediaValue) {
                        if (in_array($mediaValue, $mappedValues)) {
                            // Do not remove values already created during this process.
                            continue;
                        }
                        $mediaValues->removeElement($mediaValue);
                    }
                }
                // Create and add the value.
                $value = new Entity\Value;
                $value->setResource($media);
                $value->setType('literal');
                $value->setProperty($property);
                $value->setValue($metadata[$metadataType][$tagName]);
                $value->setIsPublic(true);
                $mediaValues->add($value);
                $mappedValues[] = $value;
            }
        }
    }

    /**
     * Perform an extract metadata action.
     *
     * There are five actions this method can perform:
     *
     * - refresh: (re)extracts metadata from files
     * - refresh_map_add: (re)extracts metadata from files and maps metadata to property values (adding to existing values)
     * - refresh_map_replace: (re)extracts metadata from files and maps metadata to property values (replacing existing values)
     * - map_add: maps metadata to property values (adding to existing values)
     * - map_replace: maps metadata to property values (replacing existing values)
     *
     * @param Entity\Media $media
     * @param string $action
     */
    public function performAction(Entity\Media $media, $action)
    {
        if (in_array($action, ['refresh', 'refresh_map_add', 'refresh_map_replace'])) {
            // Files must be stored locally to refresh extracted metadata.
            $store = $this->getServiceLocator()->get('Omeka\File\Store');
            if ($store instanceof Local) {
                $filePath = $store->getLocalPath(sprintf('original/%s', $media->getFilename()));
                $metadataEntity = $this->extractMetadata($filePath, $media->getMediaType(), $media);
                if ($metadataEntity && in_array($action, ['refresh_map_add', 'refresh_map_replace'])) {
                    $this->mapMetadata($media, $metadataEntity->getMetadata(), ('refresh_map_replace' === $action));
                }
            }
        } elseif (in_array($action, ['map_add', 'map_replace'])) {
            $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
            $metadataEntity = $entityManager->getRepository(ExtractMetadata::class)->findOneBy(['media' => $media]);
            if ($metadataEntity) {
                $this->mapMetadata($media, $metadataEntity->getMetadata(), ('map_replace' === $action));
            }
        }
    }

    /**
     * Get property by term.
     *
     * @param string $term vocabularyPrefix:propertyLocalName
     * @return null|Entity\Property
     */
    public function getPropertyByTerm($term)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        list($prefix, $localName) = array_pad(explode(':', $term), 2, null);
        $dql = '
            SELECT p
            FROM Omeka\Entity\Property p
            JOIN p.vocabulary v
            WHERE p.localName = :localName
            AND v.prefix = :prefix';
        $query = $entityManager->createQuery($dql);
        $query->setParameters([
            'localName' => $localName,
            'prefix' => $prefix,
        ]);
        return $query->getOneOrNullResult();
    }

    /**
     * Get action select element.
     *
     * @return Element\Select
     */
    public function getActionSelect()
    {
        $valueOptions = [
            'map_add' => self::ACTIONS['map_add'],
            'map_replace' => self::ACTIONS['map_replace']
        ];
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        if ($store instanceof Local) {
            // Files must be stored locally to refresh extracted metadata.
            $valueOptions = [
                'refresh' => self::ACTIONS['refresh'],
                'refresh_map_add' => self::ACTIONS['refresh_map_add'],
                'refresh_map_replace' => self::ACTIONS['refresh_map_replace'],
            ] + $valueOptions;
        }
        $element = new Element\Select('extract_metadata_action');
        $element->setLabel('Extract metadata');
        $element->setEmptyOption('[No action]'); // @translate
        $element->setValueOptions($valueOptions);
        return $element;
    }
}

<?php
namespace ExtractMetadata;

use DateTime;
use ExtractMetadata\Entity\ExtractMetadata;
use Omeka\Entity;
use Omeka\File\Store\Local;
use Omeka\Module\AbstractModule;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return array_merge(
            include sprintf('%s/config/module.config.php', __DIR__),
            include sprintf('%s/config/media_types.config.php', __DIR__)
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
         * Extract metadata before ingesting media file. This will only happen
         * when creating the media.
         */
        $sharedEventManager->attach(
            '*',
            'media.ingest_file.pre',
            function (Event $event) {
                $media = $event->getTarget();
                $tempFile = $event->getParam('tempFile');
                $this->extractMetadata($tempFile->getTempPath(), $media, $tempFile->getMediaType());
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
                $valueOptions = [
                    'map_add' => 'Map metadata (add values)', // @translate
                    'map_replace' => 'Map metadata (replace values)', // @translate
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
         * batch update data. This will signal the process to refresh or map
         * the metadata while updating each resource in the batch.
         */
        $preprocessBatchUpdate = function (Event $event) {
            $adapter = $event->getTarget();
            $data = $event->getParam('data');
            $rawData = $event->getParam('request')->getContent();
            if (isset($rawData['extract_metadata_action'])
                && in_array($rawData['extract_metadata_action'], ['refresh', 'map_add', 'map_replace'])
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
                $valueOptions = [
                    'map_add' => 'Map metadata (add values)', // @translate
                    'map_replace' => 'Map metadata (replace values)', // @translate
                    '' => '[No action]', // @translate
                ];
                if ($store instanceof Local) {
                    // Files must be stored locally to refresh extracted metadata.
                    $valueOptions = ['refresh' => 'Refresh metadata'] + $valueOptions; // @translate
                }
                $element = new \Laminas\Form\Element\Radio('extract_metadata_action');
                $element->setLabel('Extract metadata');
                $element->setValueOptions($valueOptions);
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
     * @param Entity\Media $media
     * @param string $mediaType
     * @return null|false
     */
    public function extractMetadata($filePath, Entity\Media $media, $mediaType)
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
        // Iterate each metadata type, extract the metadata using the extractor.
        $metadata = [];
        foreach ($config['extract_metadata_media_types'][$mediaType] as $metadataType => $extractorName) {
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
        // Create the metadata record.
        $entityManager = $services->get('Omeka\EntityManager');
        $entity = $entityManager->getRepository(ExtractMetadata::class)->findOneBy(['media' => $media]);
        if (!$entity) {
            $entity = new ExtractMetadata;
            $entity->setExtracted(new DateTime('now'));
            $entity->setMedia($media);
            $entityManager->persist($entity);
        }
        $entity->setMetadata($metadata);
    }

    /**
     * Perform an extract metadata action. There are two actions this method can
     * perform:
     *
     * - refresh: (re)extracts metadata from files
     * - map_add: maps metadata to property values (adding to existing values)
     * - map_replace: maps metadata to property values (replacing existing values)
     *
     * @param Entity\Media $media
     * @param string $action
     */
    public function performAction(Entity\Media $media, $action)
    {
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        // Files must be stored locally to refresh extracted metadata.
        if (('refresh' === $action) && ($store instanceof Local)) {
            $filePath = $store->getLocalPath(sprintf('original/%s', $media->getFilename()));
            $this->extractMetadata($filePath, $media, $media->getMediaType());
        } elseif ('map_add' === $action) {
            // @todo
        } elseif ('map_replace' === $action) {
            // @todo
        }
    }
}

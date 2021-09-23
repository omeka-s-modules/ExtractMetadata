<?php
/**
 * Register the metadata crosswalk.
 *
 * Key by metadataType/tagName to term (vocabularyPrefix:propertyLocalName).
 *
 * For example:
 *
 * 'exif' => [
 *     'Artist' => 'dcterms:creator',
 *     'ImageDescription' => 'dcterms:description',
 *     'CreateDate' => 'dcterms:created',
 *     'Copyright' => 'dcterms:rights',
 * ]
 *
 * Note that when using the "map_replace" action, if using identical terms, the
 * last value will overwrite all previous values that use that term.
 */
return [
    'extract_metadata_crosswalk' => [],
];

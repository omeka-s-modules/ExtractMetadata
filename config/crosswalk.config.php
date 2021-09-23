<?php
/**
 * Define the metadata crosswalk.
 *
 * Key by metadataType/tagName to term. The term is the vocabulary prefix and
 * property local name, formatted in this way: prefix:localName.
 *
 * For example:
 *
 * 'exif' => [
 *     'Artist' => 'dcterms:creator',
 *     'ImageDescription' => 'dcterms:description',
 *     'CreateDate' => 'dcterms:created',
 *     'Copyright' => 'dcterms:rights',
 * ],
 */
return [
    'extract_metadata_crosswalk' => [
    ],
];

<?php
/**
 * The metadata crosswalk.
 *
 * To define the crosswalk, you must provide an array of mapping arrays, keyed
 * by metadata type. Every mapping array must include these keys:
 *
 *   - media   (bool)   Whether to map metadata to media values
 *   - item    (bool)   Whether to map metadata to item values
 *   - pointer (string) The JSON pointer to a string in the extracted metadata
 *   - term    (string) The property term (vocabPrefix:propertyLocalName)
 *   - replace (bool)   Replace existing values: true; add to existing values: false
 *
 * Note that the "pointer" must be formatted using a JSON pointer as defined by
 * https://datatracker.ietf.org/doc/html/rfc6901
 *
 * For example:
 *
 * 'extract_metadata_crosswalk' => [
 *     'exiftool' => [
 *         [
 *             'media' => false,
 *             'item' => true,
 *             'pointer' => '/IPTC/By-line',
 *             'term' => 'dcterms:creator',
 *             'replace' => false,
 *         ],
 *         [
 *             'media' => true,
 *             'item' => true,
 *             'pointer' => '/EXIF/Copyright',
 *             'term' => 'dcterms:rights',
 *             'replace' => true,
 *         ],
 *     ],
 *     'getid3' => [
 *         [
 *             'media' => true,
 *             'item' => false,
 *             'pointer' => '/jpg/exif/IFD0/ImageDescription',
 *             'term' => 'dcterms:description',
 *             'replace' => false,
 *         ],
 *     ],
 * ],
 *
 * Here we're mapping the "By-line" of the ExifTool output to the Dublin Core
 * Creator of the item, replacing any existing Creator values; the "Copyright"
 * of the ExifTool output to the Dublin Core Rights of the media and item,
 * replacing any existing Rights values; and the "ImageDescription" of the
 * getID3 output to the Dublin Core Description of the media, adding to any
 * existing Description values.
 */
return [
    'extract_metadata_crosswalk' => [
    ],
];

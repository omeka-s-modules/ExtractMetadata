<?php
/**
 * The JSON Pointer metadata crosswalk.
 *
 * To define the crosswalk, you must provide an array of mapping arrays at the
 * bottom of this file. Every mapping array must include these keys:
 *
 *   - resource  (string) The resource to map to (media|item)
 *   - extractor (string) The extractor that extracted the metadata (exiftool, exit, etc.)
 *   - pointer   (string) The JSON pointer that resolves to a string in the extracted metadata
 *   - term      (string) The property term (vocabPrefix:propertyLocalName)
 *   - replace   (bool)   Replace existing values (true); add to existing values (false)
 *
 * Note that the "pointer" must be formatted using a JSON pointer as defined by
 * https://datatracker.ietf.org/doc/html/rfc6901
 *
 * For example:
 *
 * return [
 *     'extract_metadata_json_pointer_config' => [
 *         [
 *             'resource' => 'item',
 *             'extractor' => 'exiftool',
 *             'pointer' => '/IPTC/By-line',
 *             'term' => 'dcterms:creator',
 *             'replace' => false,
 *         ],
 *         [
 *             'resource' => 'media',
 *             'extractor' => 'exiftool',
 *             'pointer' => '/EXIF/Copyright',
 *             'term' => 'dcterms:rights',
 *             'replace' => true,
 *         ],
 *         [
 *             'resource' => 'item',
 *             'extractor' => 'exiftool',
 *             'pointer' => '/EXIF/Copyright',
 *             'term' => 'dcterms:rights',
 *             'replace' => true,
 *         ],
 *         [
 *             'resource' => 'media',
 *             'extractor' => 'getid3',
 *             'pointer' => '/jpg/exif/IFD0/ImageDescription',
 *             'term' => 'dcterms:description',
 *             'replace' => false,
 *         ],
 *     ],
 * ];
 *
 * Here we're mapping the "By-line" of the ExifTool output to the Dublin Core
 * Creator of the item, replacing any existing Creator values; the "Copyright"
 * of the ExifTool output to the Dublin Core Rights of the media and item,
 * replacing any existing Rights values; and the "ImageDescription" of the
 * getID3 output to the Dublin Core Description of the media, adding to any
 * existing Description values.
 */
return [
    'extract_metadata_json_pointer_crosswalk' => [
    ],
];

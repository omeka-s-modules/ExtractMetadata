<?php
/**
 * The metadata crosswalk.
 *
 * Use pointers defined by https://datatracker.ietf.org/doc/html/rfc6901
 *
 * For example:
 *
 * 'extract_metadata_crosswalk' => [
 *     'exiftool' => [
 *         [
 *             'pointer' => '/IPTC/By-line',
 *             'term' => 'dcterms:creator',
 *             'replace_values' => false,
 *         ],
 *         [
 *             'pointer' => '/EXIF/Copyright',
 *             'term' => 'dcterms:rights',
 *             'replace_values' => true,
 *         ],
 *     ],
 *     'getid3' => [
 *         [
 *             'pointer' => '/jpg/exif/IFD0/ImageDescription',
 *             'term' => 'dcterms:description',
 *             'replace_values' => true,
 *         ],
 *     ],
 * ],
 */
return [
    'extract_metadata_crosswalk' => [
    ],
];

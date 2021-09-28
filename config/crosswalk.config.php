<?php
/**
 * The metadata crosswalk.
 *
 * Use pointers defined by https://datatracker.ietf.org/doc/html/rfc6901
 *
 * For example, to map the "By-line" output of exiftool to Dublin Core Creator:
 *
 * 'extract_metadata_crosswalk' => [
 *     'exiftool' => [
 *         '/IPTC/By-line' => 'dcterms:creator',
 *     ],
 * ],
 */
return [
    'extract_metadata_crosswalk' => [
        'exiftool' => [
            '/IPTC/By-line' => 'dcterms:creator',
        ],
        'exif' => [
            '/IFD0/Artist' => 'dcterms:creator',
        ],
    ],
];

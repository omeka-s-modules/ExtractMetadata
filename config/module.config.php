<?php
namespace ExtractMetadata;

return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => sprintf('%s/../language', __DIR__),
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'ExtractMetadata\ExtractorManager' => Service\Extractor\ManagerFactory::class,
        ],
    ],
    'extract_metadata_extractors' => [
        'factories' => [
            'exiftool' => Service\Extractor\ExiftoolFactory::class,
        ],
    ],
    /**
     * Map file media types to the possible metadata types and their extractor.
     * The metadata type must correspond to a local name of a property in the
     * "Extract Metadata" vocabulary. The extractor must correspond to a name of
     * a registered extractor.
     */
    'extract_metadata_media_types' => [
        'image/jpeg' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
            'photoshop' => 'exiftool',
        ],
        'image/tiff' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
            'photoshop' => 'exiftool',
        ],
        'image/png' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
            'photoshop' => 'exiftool',
        ],
        'application/pdf' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
            'pdf' => 'exiftool',
        ],
    ],
];

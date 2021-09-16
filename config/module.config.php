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
    'extract_metadata_media_type_map' => [
        'image/jpeg' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
        ],
        'image/tiff' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
        ],
        'image/png' => [
            'exif' => 'exiftool',
            'iptciim' => 'exiftool',
            'xmp' => 'exiftool',
        ],
    ],
];

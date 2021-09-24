<?php
namespace ExtractMetadata;

return [
    'entity_manager' => [
        'mapping_classes_paths' => [
            sprintf('%s/../src/Entity', __DIR__),
        ],
        'proxy_paths' => [
            sprintf('%s/../data/doctrine-proxies', __DIR__),
        ],
    ],
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
    'view_manager' => [
        'template_path_stack' => [
            sprintf('%s/../view', __DIR__),
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
        'invokables' => [
            'exif' => Extractor\Exif::class,
        ],
    ],
];

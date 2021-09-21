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
    ],
];

<?php
/**
 * Register file media types and all their possible metadata types/extractors.
 */
return [
    'extract_metadata_media_types' => [
        // JPG, JPEG
        'image/jpeg' => [
            'exif' => 'exiftool',
            'icc_profile' => 'exiftool',
            'photoshop' => 'exiftool',
            'iptc' => 'exiftool',
            'xmp' => 'exiftool',
            'app14' => 'exiftool',
        ],
        // PNG
        'image/png' => [
            'xmp' => 'exiftool',
            'png' => 'exiftool',
            'iptc' => 'exiftool',
            'exif' => 'exiftool',
        ],
        // GIF
        'image/gif' => [
            'xmp' => 'exiftool',
            'gif' => 'exiftool',
            'iptc' => 'exiftool',
        ],
        // SVG
        'image/svg+xml' => [
            'svg' => 'exiftool',
            'iptc' => 'exiftool',
        ],
        // TIF, TIFF
        'image/tiff' => [
            'exif' => 'exiftool',
            'icc_profile' => 'exiftool',
            'iptc' => 'exiftool',
        ],
        // PSD
        'application/vnd.adobe.photoshop' => [
            'photoshop' => 'exiftool',
            'iptc' => 'exiftool',
            'xmp' => 'exiftool',
            'icc_profile' => 'exiftool',
            'exif' => 'exiftool',
        ],
        // PDF
        'application/pdf' => [
            'xmp' => 'exiftool',
            'pdf' => 'exiftool',
        ],
        // DOC
        'application/msword' => [
            'flashpix' => 'exiftool',
        ],
        // DOCX
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'zip' => 'exiftool',
            'xmp' => 'exiftool',
            'xml' => 'exiftool',
        ],
        // ODT
        'application/vnd.oasis.opendocument.text' => [
            'xmp' => 'exiftool',
        ],
        // ODS
        'application/vnd.oasis.opendocument.spreadsheet' => [
            'xmp' => 'exiftool',
        ],
        // ODP
        'application/vnd.oasis.opendocument.presentation' => [
            'xmp' => 'exiftool',
        ],
        // PPT
        'application/vnd.ms-powerpoint' => [
            'flashpix' => 'exiftool',
        ],
        // PPTX
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
            'zip' => 'exiftool',
            'xml' => 'exiftool',
            'xmp' => 'exiftool',
        ],
        // RTF
        'application/rtf' => [
            'rtf' => 'exiftool',
        ],
        // AVI
        'video/x-msvideo' => [
            'riff' => 'exiftool',
        ],
        // MP4
        'video/mp4' => [
            'quicktime' => 'exiftool',
        ],
        // MPG, MPEG
        'video/mpeg' => [
            'mpeg' => 'exiftool',
        ],
        // WMV
        'video/x-ms-wmv' => [
            'asf' => 'exiftool',
        ],
        'video/x-ms-asf' => [
            'asf' => 'exiftool',
        ],
        // MOV
        'video/quicktime' => [
            'quicktime' => 'exiftool',
        ],
        // OGV
        'video/ogg' => [
            'vorbis' => 'exiftool',
            'theora' => 'exiftool',
        ],
        // SWF
        'application/x-shockwave-flash' => [
            'flash' => 'exiftool',
        ],
        // MP3
        'audio/mpeg' => [
            'mpeg' => 'exiftool',
            'id3' => 'exiftool',
        ],
        // OGG, OGA
        'audio/ogg' => [
            'vorbis' => 'exiftool',
        ],
        // WAV
        'audio/wav' => [
            'riff' => 'exiftool',
        ],
        'audio/x-wav' => [
            'riff' => 'exiftool',
        ],
        // AIFF
        'audio/aiff' => [
            'aiff' => 'exiftool',
        ],
        'audio/x-aiff' => [
            'aiff' => 'exiftool',
        ],
        // FLAC
        'audio/flac' => [
            'flac' => 'exiftool',
            'vorbis' => 'exiftool',
        ],
        'audio/x-flac' => [
            'flac' => 'exiftool',
            'vorbis' => 'exiftool',
        ],
        // M4A
        'audio/m4a' => [
            'quicktime' => 'exiftool',
        ],
        // MP4
        'audio/mp4' => [
            'quicktime' => 'exiftool',
        ],
        // AAC
        'audio/aac' => [
            'quicktime' => 'exiftool',
        ],
        // OPUS
        'audio/opus' => [
            'vorbis' => 'exiftool',
            'opus' => 'exiftool',
        ],
        // ZIP
        'application/zip' => [
            'zip' => 'exiftool',
            'exe' => 'exiftool',
        ],
        // EPUB
        'application/epub+zip' => [
            'xmp' => 'exiftool',
            'xml' => 'exiftool',
        ],
    ],
];

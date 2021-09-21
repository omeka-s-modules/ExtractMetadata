<?php
/**
 * Register file media types and all their possible metadata types/extractors.
 *
 * The metadata type must correspond to a local name of a property in the
 * "Extract Metadata" vocabulary. The extractor must correspond to a name of
 * a registered extractor.
 */
return [
    'extract_metadata_media_types' => [
        // JPEG (image)
        'image/jpeg' => [
            'exif' => 'exiftool',
            'iccprofile' => 'exiftool',
            'photoshop' => 'exiftool',
            'iptc' => 'exiftool',
            'xmp' => 'exiftool',
            'app14' => 'exiftool',
        ],
        // PNG (image)
        'image/png' => [
            'xmp' => 'exiftool',
            'png' => 'exiftool',
            'iptc' => 'exiftool',
            'exif' => 'exiftool',
        ],
        // GIF (image)
        'image/gif' => [
            'xmp' => 'exiftool',
            'gif' => 'exiftool',
            'iptc' => 'exiftool',
        ],
        // SVG (image)
        'image/svg+xml' => [
            'svg' => 'exiftool',
            'iptc' => 'exiftool',
        ],
        // TIFF (image)
        'image/tiff' => [
            'exif' => 'exiftool',
            'iccprofile' => 'exiftool',
            'iptc' => 'exiftool',
            'exif' => 'exiftool',
        ],
        // PSD (image)
        'application/vnd.adobe.photoshop' => [
            'photoshop' => 'exiftool',
            'iptc' => 'exiftool',
            'xmp' => 'exiftool',
            'iccprofile' => 'exiftool',
            'exif' => 'exiftool',
        ],
        // PDF (page layout)
        'application/pdf' => [
            'xmp' => 'exiftool',
            'pdf' => 'exiftool',
        ],
        // DOC (text)
        'application/msword' => [
            'flashpix' => 'exiftool',
        ],
        // DOCX (text)
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
            'zip' => 'exiftool',
            'xmp' => 'exiftool',
            'xml' => 'exiftool',
        ],
        // ODT (text)
        'application/vnd.oasis.opendocument.text' => [
            'xmp' => 'exiftool',
        ],
        // RTF (text)
        'application/rtf' => [
            'rtf' => 'exiftool',
        ],
        // AVI (video)
        'video/x-msvideo' => [
            'riff' => 'exiftool',
        ],
        // MP4 (video)
        'video/mp4' => [
            'quicktime' => 'exiftool',
        ],
        // MPG (video)
        'video/mpeg' => [
            'mpeg' => 'exiftool',
        ],
        // WMV (video)
        'video/x-ms-wmv' => [
            'asf' => 'exiftool',
        ],
        'video/x-ms-asf' => [
            'asf' => 'exiftool',
        ],
        // MP3 (audio)
        'audio/mpeg' => [
            'mpeg' => 'exiftool',
            'id3' => 'exiftool',
        ],
        // OGG (audio)
        'audio/ogg' => [
            'vorbis' => 'exiftool',
        ],
        // WAV (audio)
        'audio/wav' => [
            'riff' => 'exiftool',
        ],
        'audio/x-wav' => [
            'riff' => 'exiftool',
        ],
    ],
];

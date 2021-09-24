# Extract Metadata

Extract embedded metadata from files.

Once installed and active, this module has the following features:

- When adding a media, the module will automatically:
    - Extract metadata from the file
    - Save the metadata alongside the media
    - Map metadata to media values (see [Metadata crosswalk](#metadata-crosswalk))
- When editing a media or batch editing media, the user can choose to:
    - Refresh the extracted metadata
    - Map metadata to media values (see [Metadata crosswalk](#metadata-crosswalk))
- When editing an item or batch editing items, the user can choose to:
    - Refresh extracted metadata of child media
    - Map metadata to child media values (see [Metadata crosswalk](#metadata-crosswalk))
- The user can view the module configuration page to see:
    - A list of extractors and whether they are available on their system
    - A table of supported media types and and all their possible metadata types/extractors

## Extractors:

### exiftool

Used to extract metadata from many files. Requires [exiftool](https://exiftool.org/).

### [More can be added]

## Supported media types:

| Media type | File extension
|-|-
| application/epub+zip | EPUB
| application/msword | DOC
| application/pdf | PDF
| application/rtf | RTF
| application/vnd.adobe.photoshop | PSD
| application/vnd.ms-powerpoint | PPT
| application/vnd.oasis.opendocument.presentation | ODP
| application/vnd.oasis.opendocument.spreadsheet | ODS
| application/vnd.oasis.opendocument.text | ODT
| application/vnd.openxmlformats-officedocument.presentationml.presentation | PPTX
| application/vnd.openxmlformats-officedocument.wordprocessingml.document | DOCX
| application/x-shockwave-flash | SWF
| application/zip | ZIP
| audio/aac | AAC
| audio/aiff | AIFF
| audio/flac | FLAC
| audio/m4a | M4A
| audio/mp4 | MP4
| audio/mpeg | MP3
| audio/ogg | OGG, OGA
| audio/opus | OPUS
| audio/wav | WAV
| audio/x-aiff | AIFF
| audio/x-flac | FLAC
| audio/x-wav | WAV
| image/gif | GIF
| image/jpeg | JPG, JPEG
| image/png | PNG
| image/svg+xml | SVG
| image/tiff | TIF, TIFF
| video/mp4 | MP4
| video/mpeg | MPG
| video/ogg | OGV
| video/quicktime | MOV
| video/x-ms-asf | ASF
| video/x-ms-wmv | WMV
| video/x-msvideo | AVI
| [More can be added] | 

Note that some file extensions or media types may be disallowed in your global settings.

You can register media types and all their possible metadata types/extractors in
`config/extract.config.php`.

## Supported metadata types:

- AIFF
- APP14
- ASF
- EXE
- Exif
- FLAC
- Flash
- FlashPix
- GIF
- ICC Profile
- ID3
- IPTC
- MPEG
- OPUS
- PDF
- Photoshop IRB
- PNG
- QuickTime
- RIFF
- RTF
- SVG
- Theora
- Vorbis
- XML
- XMP
- ZIP
- ZIP
- [More can be added]

You can register metadata types and their extractors in `config/extract.config.php`.

## Metadata crosswalk

This module adds the ability to map individual pieces of metadata to media values.
To enable this feature, you must define your own metadata crosswalk in `config/crosswalk.config.php`.

# Copyright

ExtractMetadata is Copyright © 2019-present Corporation for Digital Scholarship,
Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

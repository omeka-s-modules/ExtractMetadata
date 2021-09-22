# Extract Metadata

Extract embedded metadata from files.

Once installed and active, this module has the following features:

- The module adds an "Extract Metadata" vocabulary containing properties where it sets extracted metadata to media.
- When adding a media, the module will automatically extract metadata from the file and set them to the media.
- When editing a media or batch editing media, the user can choose to refresh or clear the extracted metadata.
- When editing an item or batch editing items, the user can choose to refresh or clear the extracted metadata of child media.
- The user can view the module configuration page to see which extractors are available on their system.

## Supported file formats:

- JPG, JPEG (image/jpeg)
- PNG (image/png)
- GIF (image/gif)
- SVG (image/svg+xml)
- TIF, TIFF (image/tiff)
- PSD (application/vnd.adobe.photoshop)
- PDF (application/pdf)
- DOC (application/msword)
- DOCX (application/vnd.openxmlformats-officedocument.wordprocessingml.document)
- ODT (application/vnd.oasis.opendocument.text)
- ODS (application/vnd.oasis.opendocument.spreadsheet)
- ODP (application/vnd.oasis.opendocument.presentation)
- PPT (application/vnd.ms-powerpoint)
- PPTX (application/vnd.openxmlformats-officedocument.presentationml.presentation)
- RTF (application/rtf)
- AVI (video/x-msvideo)
- MP4 (video/mp4)
- MPG (video/mpeg)
- WMV (video/x-ms-wmv, video/x-ms-asf)
- MOV (video/quicktime)
- OGV (video/ogg)
- SWF (application/x-shockwave-flash)
- MP3 (audio/mpeg)
- OGG, OGA (audio/ogg)
- WAV (audio/wav, audio/x-wav)
- AIFF (audio/aiff, audio/x-aiff)
- FLAC (audio/flac, audio/x-flac)
- M4A (audio/m4a)
- MP4 (audio/mp4)
- AAC (audio/aac)
- OPUS (audio/opus)
- ZIP (application/zip)
- EPUB (application/epub+zip)
- [More to be added]

Note that some file extensions or media types may be disallowed in your global settings.

## Supported metadata types:

- [Exif](https://en.wikipedia.org/wiki/Exif)
- [IPTC IIM](https://www.iptc.org/standards/iim/)
- [XMP](https://en.wikipedia.org/wiki/Extensible_Metadata_Platform)
- PDF
- Photoshop IRB
- GIF
- [ICC Profile](https://en.wikipedia.org/wiki/ICC_profile)
- PNG
- APP14
- [RIFF](https://en.wikipedia.org/wiki/Resource_Interchange_File_Format)
- MPEG
- [ID3](https://en.wikipedia.org/wiki/ID3)
- SVG
- QuickTime
- Vorbis
- ASF
- [FlashPix](https://en.wikipedia.org/wiki/FlashPix)
- ZIP
- XML
- RTF
- AIFF
- FLAC
- ZIP
- EXE
- Theora
- OPUS
- Flash
- [More to be added]

## Extractors:

### exiftool

Used to extract metadata from many files. Requires [exiftool](https://exiftool.org/).

### [More to be added, if needed]

## Disabling metadata extraction

You can disable metadata extraction for a specific media type by commenting out
the media type in the media types file (config/media_types.config.php). For example,
if you want to disable extractions for MP3 (audio/mpeg) files, comment out the following:

```php
// 'audio/mpeg' => [
//     'mpeg' => 'exiftool',
//     'id3' => 'exiftool',
// ],
```

# Copyright

ExtractMetadata is Copyright © 2019-present Corporation for Digital Scholarship,
Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

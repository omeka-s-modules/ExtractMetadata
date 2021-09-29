# Extract Metadata

Extract embedded metadata from files.

Once installed and active, this module has the following features:

- When adding a media, the module will automatically:
    - Extract metadata from the file and save the metadata alongside the media
    - Map metadata to media values (see [Metadata crosswalk](#metadata-crosswalk))
- When editing a media or batch editing media, the user can choose to:
    - Refresh the extracted metadata
    - Map metadata to media values (see [Metadata crosswalk](#metadata-crosswalk))
- When editing an item or batch editing items, the user can choose to:
    - Refresh extracted metadata of child media
    - Map metadata to child media values (see [Metadata crosswalk](#metadata-crosswalk))
- The user can view the module configuration page to see:
    - The list of extractors and whether they are available on their system
    - The metadata crosswalk (if defined)

## Extractors:

### ExifTool

Used to extract many types of metadata from many types of files. Requires the
[ExifTool](https://exiftool.org/) command-line application.

### exif

Used to extract EXIF metadata that is commonly found in JPEG and TIFF files. Requires
PHP's [exif](https://www.php.net/manual/en/book.exif.php) extension.

### getID3

Used to extract many types of metadata from many types of files. Uses the
[getID3](https://github.com/JamesHeinrich/getID3) PHP library.

### Tika

Used to extract many types of metadata from many types of files. Requires the
[Apache Tika](https://tika.apache.org/) content analysis toolkit. Java must be installed
and the path to the `tika-app-*.jar` file must be configured in `module.config.php`
under `[extract_metadata_extractor_config][tika][jar_path]`.

### [More can be added]

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

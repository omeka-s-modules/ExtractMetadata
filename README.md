# Extract Metadata

Extract embedded metadata from files. Once installed and active, this module has
the following features:

- When configuring the module, the user can enable/diable extractors and mappers.
- When adding a media, the module will automatically:
    - Extract metadata from the file;
    - Save the metadata alongside the media;
    - Map metadata to resource values.
- When editing a media/item or batch editing media/item, the user can choose to
  perform a number of actions:
    - Refresh metadata: (re)extract metadata from files;
    - Refresh and map metadata: (re)extract metadata from files and map metadata to resource values;
    - Map metadata: Map extracted metadata to resource values;
    - Delete metadata: Delete extracted metadata.

## Extractors:

Extractors extract metadata from files. Note that extractors must be enabled on
the module configuration page. This module comes with four extractors, but more
can be added depending on your need.

### exiftool

Used to extract many types of metadata from many types of files. Requires the
[ExifTool](https://exiftool.org/) command-line application.

### exif

Used to extract EXIF metadata that is commonly found in JPEG and TIFF files. Requires
PHP's [exif](https://www.php.net/manual/en/book.exif.php) extension.

### getid3

Used to extract many types of metadata from many types of files. Uses the
[getID3](https://github.com/JamesHeinrich/getID3) PHP library.

### tika

Used to extract many types of metadata from many types of files. Requires the
[Apache Tika](https://tika.apache.org/) content analysis toolkit. Java must be installed
and the path to the `tika-app-*.jar` file must be configured in `config/module.config.php`
under `[extract_metadata_extractor_config][tika][jar_path]`.

## Mappers

Mappers map extracted metadata to resource values. Note that mappers must be enabled
on the module configuration page. This module comes with one mapper, but more can
be added depending on your need.

### jsonPointer

Used to map metadata to resource values using [JSON pointers](https://datatracker.ietf.org/doc/html/rfc6901).
You must define your own metadata crosswalk in `config/json_pointer_crosswalk.config.php` under
`[extract_metadata_json_pointer_crosswalk]`.

# Copyright

ExtractMetadata is Copyright © 2019-present Corporation for Digital Scholarship,
Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

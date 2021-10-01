# Extract Metadata

Extract embedded metadata from files.

Once installed and active, this module has the following features:

- The user can enable/diable extractors and mappers on the module configuration page.
- When adding a media, the module will automatically extract metadata from the file,
  save the metadata alongside the media, and map metadata to resource values.
- When editing a media or batch editing media, the user can choose to refresh the
  extracted metadata and/or map metadata to resource values.
- When editing an item or batch editing items, the user can choose to refresh extracted
  metadata of child media and/or map metadata to resource values.

## Extractors:

Extractors extract metadata from files.  Note that extractors must be enabled on
the module configuration page.This module comes with four extractors, but more can
be added depending on your need.

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

### [custom extractors can be added]

## Mappers

Mappers map extracted metadata to resource values. Note that mappers must be enabled
on the module configuration page. This module comes with one mapper, but more can
be added depending on your need.

### JSON Pointer

Used to map metadata to resource values using [JSON pointers](https://datatracker.ietf.org/doc/html/rfc6901).
You must define your own metadata crosswalk in `config/json_pointer_crosswalk.php`.

# Copyright

ExtractMetadata is Copyright © 2019-present Corporation for Digital Scholarship,
Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

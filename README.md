# Extract Metadata

Extract embedded metadata from files, such as photograph metadata embedded when a digital camera takes a picture. 

The module adds a new "Extract metadata" tab to each media page, which will allow you to manually extract any embedded metadata found on the file. It also adds an "Extract metadata" field to the batch-editing page. When enabled, it automatically works on newly uploaded files.

A good workflow is to install and configure the module, batch-extract metadata from existing files in your database, map them to your chosen media metadata fields, and then simply allow the module to continue extracting metadata from files in the future.

- When configuring the module, the user can:
    - View and enable/disable extractors;
    - View and enable/disable mappers;
    - Configure the metadata crosswalk for the JSON Pointer mapper (if enabled).
- When adding media, the module will automatically:
    - Extract metadata from the file;
    - Save the metadata alongside the file;
    - Map metadata to resource values.
- When editing a media/item or batch-editing media/items, the user can choose to perform a number of actions:
    - Refresh metadata: (re)extract metadata from files;
    - Refresh and map metadata: (re)extract metadata from files and map metadata to resource values;
    - Map metadata: Map extracted metadata to resource values;
    - Delete metadata: Delete extracted metadata.
- When viewing and editing media, the user can see the extracted metadata in the "Extract metadata" section.

## Extractors:

Extractors extract metadata from files. Note that extractors must be enabled on the module configuration page. This module comes with four extractors, but more can be added depending on your need.

### ExifTool

Used to extract many types of metadata from many types of files. Requires the [ExifTool](https://exiftool.org/) command-line application.

### Exif

Used to extract EXIF metadata that is commonly found in JPEG and TIFF files. Requires PHP's [exif](https://www.php.net/manual/en/book.exif.php) extension.

### getID3

Used to extract many types of metadata from many types of files. Uses the [getID3](https://github.com/JamesHeinrich/getID3) PHP library, which comes with this module.

### Tika

Used to extract many types of metadata from many types of files. Requires the [Apache Tika](https://tika.apache.org/) content analysis toolkit. Java must be installed and the path to the `tika-app-*.jar` file must be configured in `config/module.config.php` under `[extract_metadata_extractor_config][tika][jar_path]`.

## Mappers

Mappers map extracted metadata to resource values. Note that a mapper must be enabled on the module configuration page. This module comes with one mapper, but more can be added depending on your need.

### JSON Pointer

Used to map metadata to resource values using [JSON pointers](https://datatracker.ietf.org/doc/html/rfc6901). You must define your own metadata crosswalk in the module configuration page under "JSON Pointer crosswalk".

One common example is to map a JPEG file's creation date to Dublin Core's "Date Created" property:

- Resource: [Media or Item]
- Extractor: "Exif"
- Pointer: `/EXIF/DateTimeOriginal`
- Property: "Dublin Core : Date Created"
- Replace values: [checked or unchecked]

Note that the pointer points to the DateTimeOriginal value in the Exif metadata output, which you can view in a JPEG media's "Extract metadata" section. Once you've saved this map, perform the "Map metadata" action as described above and, if your JPEG file includes DateTimeOriginal, the media/item should now have a "Date Created" value.

## IIIF Presentation module

This module can automatically provide accurate width, height, and duration metadata for IIIF content resources published by the [IIIF Presentation module](https://github.com/omeka-s-modules/IiifPresentation). This is useful for IIIF viewers that require strict validation against the IIIF specification. Note that the metadata is only available if the metadata has already been extracted by the ExifTool extractor.

# Copyright

ExtractMetadata is Copyright © 2019-present Corporation for Digital Scholarship,
Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

## INTRODUCTION

The Media Import module will help me attach images to my bicycle tours.
This module is not for general consumption as it is part of my custom setup.

Given a directory of images already on the server. this module will import them as media entities, and attach them to a tour.

## Update 1/24/2026 - PL
This module has been greatly expanded to use the exif module to read metadata from the images and save it to various fields and vocabularies. It also uses the nominatim.openstreetmap.org service to fetch location data given the GPS coordinates from the exif data. The functionality is exposed three ways.
- During the media import
- When adding a media image through the UI
- Through a Drush command that will go through existing media files and geo tag them.


## REQUIREMENTS

There is a dependency in the exif module

## INSTALLATION

Code lives at https://github.com/lieb77/media_import

## CONFIGURATION

## MAINTAINERS

Current maintainers for Drupal 11:

- Paul Lieberman (lieb) - https://www.drupal.org/u/lieb


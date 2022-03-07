CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

[![Build status]][build]

The Media Instagram module integrates Instagram posts as a media source. It
supports retrieval of image posts and the first image of album posts from a
single Instagram user.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > People > Permissions and configure
       permissions of the module
    3. Navigate to Administration > Web services > Link Instagram and follow the
       login procedure (their Drupal user must have the "Link Instagram account"
       permission to access this page) to link an Instagram account to the
       site.
    4. Navigate to Administration > Structure > Media types and add a new media
       type using the "Instagram" source. One can also set periodic automatic
       fetching of posts via cron.


[Build status]: https://github.com/projectcosmic/media_instagram/actions/workflows/ci.yml/badge.svg
[build]: https://github.com/projectcosmic/media_instagram/actions/workflows/ci.yml

imdbphp
=======

PHP library for retrieving film and TV information from IMDb.
Retrieve most of the information you can see on IMDb including films, TV series, TV episodes, people.
Search for titles on IMDb, including filtering by type (film, tv series, etc).
Download film posters and actor images.


Quick Start
===========

* Include [imdbphp/imdbphp](https://packagist.org/packages/imdbphp/imdbphp) using [composer](https://www.getcomposer.org), clone this repo or download the latest [release zip](https://github.com/tboothman/imdbphp/releases).
* Find a film you want the metadata for e.g. Lost in translation http://www.imdb.com/title/tt0335266/
* If you're not using composer or an autoloader include `bootstrap.php`.
* Get some data
```php
$title = new \Imdb\Title(335266);
$rating = $title->rating();
$plotOutline = $title->plotoutline();


Installation
============

This library scrapes imdb.com so changes their site can cause parts of this library to fail. You will probably need to update a few times a year. Keep this in mind when choosing how to install/configure.

Get the files with one of:
* [Composer](https://www.getcomposer.org)
* Git clone. Checkout the latest release tag.
* [Zip/Tar download]

### Requirements
* PHP >= 7.4
* PHP cURL extension


Configuration
=============

imdbphp needs no configuration by default but can change languages if configured.

Configuration is done by the `\Imdb\Config` class in `src/Imdb/Config.php` which has detailed explanations of all the config options available.
You can alter the config by creating the object, modifying its properties then passing it to the constructor for imdb.
```php
$config = new \Imdb\Config();
$config->language = 'de-DE,de,en';
$imdb = new \Imdb\Title(335266, $config);
$imdb->title(); // Lost in Translation - Zwischen den Welten
$imdb->orig_title(); // Lost in Translation
```
```

Searching for a film
====================

```php
// include "bootstrap.php"; // Load the class in if you're not using an autoloader
$search = new \Imdb\TitleSearch(); // Optional $config parameter
$results = $search->search('The Matrix');

// $results is an array of Titles
// The array will have title, imdbid, year and movietype available
// immediately, but any other data will have to be fetched from IMDb
```


Gotchas / Help
==============
SSL certificate problem: unable to get local issuer certificate
---------------------------------------------------------------
### Windows
The cURL library either hasn't come bundled with the root SSL certificates or they're out of date. You'll need to set them up:
1. [Download cacert.pem](https://curl.haxx.se/docs/caextract.html)  
2. Store it somewhere in your computer.  
`C:\php\extras\ssl\cacert.pem`  
3. Open your php.ini and add the following under `[curl]`  
`curl.cainfo = "C:\php\extras\ssl\cacert.pem"`  
4. Restart your webserver.  
### Linux
cURL uses the certificate authority file that's part of linux by default, which must be out of date. 
Look for instructions for your OS to update the CA file or update your distro.

Configure languages
---------------------------------------------------------------
Sometimes IMDb gets unsure that the specified language are correct, if you only specify your unique language and territory code (de-DE). In the example below, you can find that we have chosen to include `de-DE (German, Germany)`, `de (German)` and `en (English)`. If IMDb can’t find anything matching German, Germany, you will get German results instead or English if there are no German translation.
```php
$config = new \Imdb\Config();
$config->language = 'de-DE,de,en';
$imdb = new \Imdb\Title(335266, $config);
$imdb->title(); // Lost in Translation - Zwischen den Welten
$imdb->orig_title(); // Lost in Translation
```
Please use The Unicode Consortium [Langugage-Territory Information](http://www.unicode.org/cldr/charts/latest/supplemental/language_territory_information.html) database for finding your unique language and territory code.

| Langauge | Code | Territory   | Code |
| -------- | ---- | ----------- | ---- |
| German   | de   | Germany {O} | DE   |

After you have found your unique language and territory code you will need to combine them. Start with language code (de), add a separator (-) and at last your territory code (DE); `de-DE`. Now include your language code (de); `de-DE,de`. And the last step add English (en); `de-DE,de,en`.

imdbphp6
=======

PHP library for retrieving film and TV information from IMDb.<br>
Retrieve most of the information you can see on IMDb including films, TV series, TV episodes, people.<br>
Search for titles on IMDb.<br>
Download film posters, actor and episode images.<br>
imdbphp6 is not a fork of imdbphp although there are things the same. It is a stripped down version adjusted for my personal needs.<br>
There is a full list of all methods, descriptions and outputs in the wiki.
https://github.com/duck7000/imdbphp6/wiki


Quick Start
===========

* Clone this repo or download the latest [release zip] (No release jet)
* Find a film you want the data for e.g. A clockwork orange https://www.imdb.com/title/tt0066921/
* If you're not using composer or an autoloader include `bootstrap.php`.
* Get some data

For titles:
```php
$title = new \Imdb\Title(335266);
$rating = $title->rating();
$plotOutline = $title->plotoutline();
```

For persons:
```php
$name = new \Imdb\Name(0000154);
$name = $name->name();
$nickname = $name->nickname();
```

Installation
============

This library uses GraphQL API from imdb to get the data, so changes are not very often to be expected.<br>
The data received from imdb GraphQL API could however be different as this data is in the purest form compared to previous methods.<br>
There seems to be a limit on maximum episodes per season of 250, this may also be true for year based tv series.<br>
Thanks to @tBoothman for his groundwork to make this possible.

Get the files with one of:
* Git clone. Checkout the latest release tag (No release jet).
* [Zip/Tar download]

### Requirements
* PHP >= recommended 8.1 (it works from 5.6 - 8.1) Remember all versions < 8.0 are EOL!
* PHP cURL extension


Configuration
=============

imdbphp6 needs no configuration by default but you can change languages if configured.

Configuration is done by the `\Imdb\Config` class in `src/Imdb/Config.php` which has detailed explanations of all the config options available.
You can alter the config by creating the object, modifying its properties then passing it to the constructor for imdb.
```php
$config = new \Imdb\Config();
$config->language = 'de-DE,de,en';
$imdb = new \Imdb\Title(335266, $config);
$imdb->title(); // Lost in Translation - Zwischen den Welten
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

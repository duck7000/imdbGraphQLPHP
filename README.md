imdbphp6
=======

PHP library for retrieving film and TV information from IMDb.<br>
Retrieve most of the information you can see on IMDb including films, TV series, TV episodes, people and coming soon releases.<br>
Search for titles on IMDb.<br>
Download film posters, actor, recommendations, foto's and episode images.<br>
The results can be localized and now also cached! Localization only seems to effect title, photo, plotoutline and recommendations (titles only). Check wiki homepage to enable.<br>
imdbphp6 is not a complete fork of imdbphp although there are things simular.<br>
There is a full list of all methods, descriptions and outputs in the wiki.
https://github.com/duck7000/imdbphp6/wiki


Quick Start
===========

* Clone this repo or download the latest [release zip]
* Find a film you want the data for e.g. A clockwork orange https://www.imdb.com/title/tt0066921/
* Include `bootstrap.php`.
* Get some data

For title search:
```php
$imdb = new \Imdb\TitleSearch();
$results = $imdb->search("1408", "MOVIE,TV");
```

For Advanced title search:
```php
$imdb = new \Imdb\TitleSearchAdvanced();
$results = $imdb->advancedSearch($genres, $types, $creditId, $startDate, $endDate);
All info is in the wiki page
```

For titles:
```php
$title = new \Imdb\Title("335266");
$rating = $title->rating();
$plotOutline = $title->plotoutline();
```

For persons:
```php
$name = new \Imdb\Name("0000154");
$name = $name->name();
$nickname = $name->nickname();
```

For Calendar:
```php
$calendar = new \Imdb\Calendar();
$releases = $calendar->comingSoon();
```

Installation
============

This library uses GraphQL API from imdb to get the data, so changes are not very often to be expected.<br>
The data received from imdb GraphQL API could however be different as this data is in the purest form compared to previous methods.<br>
There seems to be a limit on maximum episodes per season of 250, this may also be true for year based tv series.<br>
Thanks to @tBoothman for his groundwork to make this possible!

Get the files with one of:
* Git clone. Checkout the latest release tag
* [Zip/Tar download]

### Requirements
* PHP >= recommended 8.1 (it works from 5.6 - 8.1) Remember all versions < 8.0 are EOL!
* PHP cURL extension


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

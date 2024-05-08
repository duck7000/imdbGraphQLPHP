imdbphp6
=======

PHP library for retrieving film and TV information from IMDb.<br>
This library uses GraphQL API from imdb to get the data.<br>
The data received from imdb GraphQL API could however be different compared to previous scraper methods.<br>
Thanks to @tBoothman for his groundwork to make this possible!<br><br>

imdbphp6 is NOT a fork, it is based on imdbphp<br>
This library uses GraphQL API from imdb to get the data. (Thanks to @tboothman)<br>
Retrieve all information from IMDb including films, TV series, TV episodes, people and coming soon releases.<br>
Search for titles on IMDb.<br>
Download film posters, actor, recommendations, foto's and episode images.<br>
The results can be localized and cached.<br>
Localization only seems to effect title, photo, plotoutline and recommendations (titles only). Check wiki homepage to enable.<br>
There is a full list of all methods, descriptions and outputs in the wiki.
https://github.com/duck7000/imdbphp6/wiki


Quick Start
===========

* Clone this repo or download the latest [release zip]
* Find a film you want the data for e.g. A clockwork orange https://www.imdb.com/title/tt0066921/
* Include `bootstrap.php`.
* Get some data

For Title search:
```php
$imdb = new \Imdb\TitleSearch();
$results = $imdb->search("1408", "MOVIE,TV", "1955-01-01", "2000-01-01");
All info is in the wiki page
```

For Advanced title search:
```php
$imdb = new \Imdb\TitleSearchAdvanced();
$results = $imdb->advancedSearch($searchTerm, $genres, $types, $creditId, $startDate, $endDate, $countryId, $languageId);
All info is in the wiki page
```

For Titles:
```php
$title = new \Imdb\Title("335266");
$rating = $title->rating();
$plotOutline = $title->plotoutline();
```
For Name search:
```php
$imdb = new \Imdb\NameSearch();
$results = $imdb->search("Peter Fonda");
```

For Names:
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

Download the latest version or latest git version and extract it to your webserver. Use one of the above methods to get some results

Get the files with one of:
* Git clone. Checkout the latest release tag
* [Zip/Tar download]

### Requirements
* PHP >= recommended 8.1 (it works from 5.6 - 8.1) Remember all versions < 8.0 are EOL!
* PHP cURL extension

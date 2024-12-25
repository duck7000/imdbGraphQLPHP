<?php

#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Configuration class for imdbGraphQLPHP
 * @author ed (github user: duck7000)
 */
class Config
{

    #========================================================[ Cache options]===
    /**
     * Directory to store cached pages. This must be writable by the web
     * server. It doesn't need to be under documentroot.
     * @var string
     */
    public $cacheDir = './cache/';

    /**
     * Use cached pages if available?
     * @var boolean
     */
    public $cacheUse = false;

    /**
     * Store the pages retrieved for later use?
     * @var boolean
     */
    public $cacheStore = false;

    /**
     * Use zip compression for caching the retrieved html-files?
     * @see $converttozip if you're changing from false to true
     * @var boolean
     */
    public $cacheUseZip = true;

    /**
     * Convert non-zip cache-files to zip
     * You might want to use this if you weren't gzipping your cache files, but now are. They will be rewritten when they're used
     * @var boolean
     */
    public $cacheConvertZip = false;

    /**
     * Cache expiration time - cached pages older than this value (in seconds) will
     * be automatically deleted.
     * If 0 cached pages will never expire
     * @var integer
     */
    public $cacheExpire = 604800;

    /**
     * Where to store images retrieved from the IMDB site by the method photoLocalurl().
     * This needs to be under documentroot to be able to display them on your pages.
     * @var string
     */
    public $photodir = './images/';

    /**
     * URL corresponding to photodir, i.e. the URL to the images, i.e. start at
     * your servers DOCUMENT_ROOT when specifying absolute path
     * @var string
     */
    public $photoroot = './images/';


    #========================================================[ Localization options ]===
     /**
     * @var boolean useLocalization set true to use localization
     * leave this to false if you want US American English
     */
    public $useLocalization = false;

    /**
     * @var string country set country code
     * possible values:
     * CA (Canada)
     * FR (France)
     * DE (Germany)
     * IN (Indonesia)
     * IT (Italy)
     * BR (Brazil)
     * ES (Spain)
     * MX (Mexico)
     */
    public $country = "DE";

    /**
     * @var string language set language code
     * possible values:
     * fr-CA (French Canada)
     * fr-FR (French France)
     * de-DE (German Germany)
     * hi-IN (hindi Indonesia)
     * it-IT (Italian Italy)
     * pt-BR (Portugues Brazil)
     * es-ES (Spanisch Spain)
     * es-MX (Spanisch Mexico)
     */
    public $language = "de-DE";

    #========================================================[ TitleSearch options ]===
    /**
     * amount of search results for Title search
     * @var int default: 10
     */
    public $titleSearchAmount = 10;


    #========================================================[ NameSearch options ]===
    /**
     * amount of search results for Name search
     * @var int default: 10
     */
    public $nameSearchAmount = 10;


    #========================================================[ KeywordSearch options ]===
    /**
     * amount of search results for Keyword search
     * @var int default: 30
     */
    public $keywordSearchAmount = 30;


    #========================================================[ CompanySearch options ]===
    /**
     * amount of search results for Company search
     * @var int default: 30
     */
    public $companySearchAmount = 30;


    #========================================================[ TitleSearchAdvanced options ]===
    /**
     * amount of search results
     * @var int (should be 250 or less, more is not very useful)
     */
    public $titleSearchAdvancedAmount = 250;

    /**
     * Sort by options
     * @var ENUM with double quotes.
     * Possible values:
     *
     * BOX_OFFICE_GROSS_DOMESTIC
     *      Gross revenue pulled in via box-office in Domestic market for entire lifetime of title.
     *      Domestic refers to North America (U.S., Canada, and Puerto Rico)
     *      ASC: Lower numbers means the title has pulled in less box-office revenue, so poorer performing titles will be first.
     *
     * METACRITIC_SCORE
     *      Overall Metascore based on critic reviews. Titles without a metascore are
     *      placed at the end when using ASC sort order.
     *      ASC: Lower Metacritic score means the title is rated more poorly, so titles with worse scores will be first.
     *
     * MY_RATING
     *      Star Rating given by the requesting user.
     *      ASC: Lower star rating means the title the user rated the title more poorly, so most disliked titles will be first.
     *
     * MY_RATING_DATE
     *      Date when customer rated a title.
     *      ASC: Earlier (older) ratings will be first.
     *
     * POPULARITY
     *      TitleMeterType.TITLE_METER (aka Pro MOVIEMeter). Score given to non-episodic title types.
     *      ASC: Lower popularity score means that the title is more popular, so the most popular titles will be first.
     *
     * RANKING
     *      Sort results based on specified ranking algorithm. For the advancedTitleSearch query, exactly one ranked title list
     *      constraint must be specified for using this sort option.
     *      ASC: Higher ranks will be first.
     *
     * RELEASE_DATE
     *      Earliest wide release date of a title. Titles without a release date are
     *      placed at the end when using ASC sort order.
     *      ASC: Earlier (older) released title will be first.
     *
     * RUNTIME
     *      The length of the title in terms of runtime.
     *      ASC: Lower runtime means the title is shorter, so shortest titles will be first.
     *
     * TITLE_REGIONAL
     *      Alphabetical sorting based on regional title text as determined by user language preferences.
     *      Language preference is determined by x-imdb-user-country and x-imdb-user-language headers.
     *      Only supports the languages/regions we support for localized search. Defaults to original title otherwise.
     *      ASC: Lower numbers and letters near the top of the alphabet will be returned first.
     *
     * USER_RATING
     *      Weighted IMDb Star Rating as determined by users
     *      Note: IMDb maintains a threshold to a minimum number of ratings before it is considered.
     *      ASC: Lower star rating means the title is rated more poorly, so titles with worse ratings will be first.
     *
     * USER_RATING_COUNT
     *      Count of ratings given by users
     *      Note: IMDb maintains a threshold to a minimum number of ratings before it is considered.
     *      ASC: Lower count of ratings means the title has been rated a fewer number of
     *      times, so titles with least ratings will be first.
     *
     * YEAR
     *      The recognized year of the title. Typically, the release year, but guidelines are here:
     *      https://help.imdb.com/article/contribution/titles/title-formatting/G56U5ERK7YY47CQB
     *      ASC: Earlier (older) titles will be first.
     */
    public $sortBy = "POPULARITY";

    /**
     * Sort order options
     * @var ENUM
     * Possible values:
     *
     * ASC
     *      Ascending order e.g. 1,2,3
     *
     * DESC
     *      Descending order e.g. 3,2,1
     */
    public $sortOrder = "ASC";


    #========================================================[ NameSearchAdvanced options ]===
    /**
     * amount of search results (Default: 250)
     * @var int (should be 250 or less, more is not very useful)
     */
    public $nameSearchAdvancedAmount = 250;

    /**
     * Sort by options
     * @var string with double quotes (Default: POPULARITY
     * Possible values:
     *
     * BIRTH_DATE
     *      Sort names based on their birth date
     *      ASC: Earliest dates to Highest dates (Oldest - Youngest)
     *
     * DEATH_DATE
     *      Sort names based on their death date
     *      ASC: Earliest dates to last dates (Died First - Died Last)
     *
     * NAME
     *      Sort Names alphabetically
     *      ASC: A-Z
     *
     * POPULARITY
     *      Sort Names based on their starMeterCurrentWeekRank
     *      ASC: Lower popularity score means that the name is more popular, so the most popular names will be first.
     *
     */
    public $nameSortBy = "POPULARITY";

    /**
     * Sort order options (Default: ASC
     * @var ENUM
     * Possible values:
     *
     * ASC
     *      Ascending order e.g. 1,2,3
     *
     * DESC
     *      Descending order e.g. 3,2,1
     */
    public $nameSortOrder = "ASC";


    #========================================================[ Calendar class options ]===

    #---------------------------------------comingSoonStreaming]---
    /**
     * Sort by options
     * @var string with double quotes (Default: LIST_ORDER)
     * Possible values:
     *
     * LIST_ORDER
     *      Sort titles based on their list order
     *
     * CREATED_DATE
     *      Sort titles based on their created date
     *
     * MODIFIED_DATE
     *      Sort titles based on their modified date
     *
     * POPULARITY
     *      Sort Names based on their starMeterCurrentWeekRank
     *      ASC: Lower popularity score means that the name is more popular, so the most popular names will be first.
     *
     */
    public $streamSortBy = "LIST_ORDER";

    /**
     * Sort order options (Default: ASC
     * @var ENUM
     * Possible values:
     *
     * ASC
     *      Ascending order e.g. 1,2,3
     *
     * DESC
     *      Descending order e.g. 3,2,1
     */
    public $streamSortOrder = "ASC";

    #========================================================[ Thumbnail options ]===

    #---------------------------------------[Title and TitleCombined class Photo thumbnail]---
    /**
     * photo() thumbnail width
     * Default value: 190
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $photoThumbnailWidth = 190;

    /**
     * photo() thumbnail height
     * Default value: 281
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $photoThumbnailHeight = 281;

    #----------------------------------------[Title class Recommendation thumbnail]---
    /**
     * recommendation() thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $recommendationThumbnailWidth = 140;

    /**
     * recommendation() thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $recommendationThumbnailHeight = 207;

    #----------------------------------------[Title class Cast thumbnail]---
    /**
     * cast() thumbnail width
     * Default value: 32
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $castThumbnailWidth = 32;

    /**
     * cast() thumbnail height
     * Default value: 44
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $castThumbnailHeight = 44;

    #----------------------------------------[Title class Episodes thumbnail]---
    /**
     * episode() thumbnail width
     * Default value: 224
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $episodeThumbnailWidth = 224;

    /**
     * episode() thumbnail height
     * Default value: 126
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $episodeThumbnailHeight = 126;

    #----------------------------------------[Title and Name class mainphoto thumbnail]---
    /**
     * mainphoto() thumbnail height
     * Default value: 100
     * @var int pixels
     */
    public $mainphotoThumbnailHeight = 100;

    #---------------------------------------[Name class Photo thumbnail]---
    /**
     * photo() thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $namePhotoThumbnailWidth = 140;

    /**
     * photo() thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $namePhotoThumbnailHeight = 207;

    #---------------------------------------[Trailers and Chart class Photo thumbnail]---
    /**
     * ALL methods thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $thumbnailWidth = 140;

    /**
     * ALL methods thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $thumbnailHeight = 207;

    #---------------------------------------[Calendar class thumbnail]---
    /**
     * All methods thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind square thumbnails don't work
     */
    public $calendarThumbnailWidth = 140;

    /**
     * All methods thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $calendarThumbnailHeight = 207;

    #---------------------------------------[NameSearchAdvanced class thumbnail]---
    /**
     * All methods thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind square thumbnails don't work
     */
    public $nameSearchAdvancedThumbnailWidth = 140;

    /**
     * All methods thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $nameSearchAdvancedThumbnailHeight = 207;

    #---------------------------------------[TitleSearchAdvanced class thumbnail]---
    /**
     * All methods thumbnail width
     * Default value: 140
     * @var int pixels
     * Keep ratio in mind square thumbnails don't work
     */
    public $titleSearchAdvancedThumbnailWidth = 140;

    /**
     * All methods thumbnail height
     * Default value: 207
     * @var int pixels
     * Keep ratio in mind, square thumbnails don't work
     */
    public $titleSearchAdvancedThumbnailHeight = 207;

    #========================================================[ Debug / Expert options ]===
    /**
     * Enable debug mode?
     * @var boolean
     */
    public $debug = false;

    /**
     * Set curlopt_timout, this is the time out if curl has a connection problem
     * @var int
     */
    public $curloptTimeout = 30;

}

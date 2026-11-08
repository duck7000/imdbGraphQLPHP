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
     * server. It doesn't need to be under documentroot
     * @var string
     */
    public $cacheDir = './cache/';

    /**
     * Use cached pages if available?
     * @var bool
     */
    public $cacheUse = false;

    /**
     * Store the pages retrieved for later use?
     * @var bool
     */
    public $cacheStore = false;

    /**
     * Use zip compression for caching the retrieved html-files?
     * @note If you are experience any issues when enabling try disable it again as the cache file sizes aren't that big
     * @see $converttozip if you're changing from false to true
     * @var bool
     */
    public $cacheUseZip = false;

    /**
     * Convert non-zip cache-files to zip
     * @note You might want to use this if you weren't gzipping your cache files, but now are. They will be rewritten when they're used
     * @var boolean
     */
    public $cacheConvertZip = false;

    /**
     * Cache expiration time - cached pages older than this value (in seconds) will be automatically deleted
     * @note If 0 cached pages will never expire
     * @var int
     */
    public $cacheExpire = 604800;


    #========================================================[ Localization options ]===
     /**
     * Use Localization?
     * @var bool
     * @note leave this to false if you want default US American English
     */
    public $useLocalization = false;

    /**
     * Set country code
     * @var string
     * possible values:
     *      CA (Canada)
     *      FR (France)
     *      DE (Germany)
     *      IN (Indonesia)
     *      IT (Italy)
     *      BR (Brazil)
     *      ES (Spain)
     *      MX (Mexico)
     */
    public $country = "DE";

    /**
     * Set language code
     * @var string
     * possible values:
     *      fr-CA (French Canada)
     *      fr-FR (French France)
     *      de-DE (German Germany)
     *      hi-IN (hindi Indonesia)
     *      it-IT (Italian Italy)
     *      pt-BR (Portugues Brazil)
     *      es-ES (Spanisch Spain)
     *      es-MX (Spanisch Mexico)
     */
    public $language = "de-DE";

    #========================================================[ TitleSearch options ]===
    /**
     * Amount of search results for Title search
     * @note default: 10
     * @var int
     */
    public $titleSearchAmount = 10;


    #========================================================[ NameSearch options ]===
    /**
     * Amount of search results for Name search
     * @note default: 10
     * @var int
     */
    public $nameSearchAmount = 10;


    #========================================================[ KeywordSearch options ]===
    /**
     * Amount of search results for Keyword search
     * @note default: 30
     * @var int
     */
    public $keywordSearchAmount = 30;


    #========================================================[ CompanySearch options ]===
    /**
     * Amount of search results for Company search
     * @note default: 30
     * @var int
     */
    public $companySearchAmount = 30;


    #========================================================[ TitleSearchAdvanced options ]===
    /**
     * Amount of search results
     * @note should be 250 or less
     * @var int
     */
    public $titleSearchAdvancedAmount = 250;

    /**
     * TitleSearchAdvanced Sort by options
     * @var string
     * Possible values:
     * BOX_OFFICE_GROSS_DOMESTIC
     *      Gross revenue pulled in via box-office in Domestic market for entire lifetime of title.
     *      Domestic refers to North America (U.S., Canada, and Puerto Rico)
     *      ASC: Lower numbers means the title has pulled in less box-office revenue, so poorer performing titles will be first.
     * METACRITIC_SCORE
     *      Overall Metascore based on critic reviews. Titles without a metascore are
     *      placed at the end when using ASC sort order.
     *      ASC: Lower Metacritic score means the title is rated more poorly, so titles with worse scores will be first.
     * MY_RATING
     *      Star Rating given by the requesting user.
     *      ASC: Lower star rating means the title the user rated the title more poorly, so most disliked titles will be first.
     * MY_RATING_DATE
     *      Date when customer rated a title.
     *      ASC: Earlier (older) ratings will be first.
     * POPULARITY
     *      TitleMeterType.TITLE_METER (aka Pro MOVIEMeter). Score given to non-episodic title types.
     *      ASC: Lower popularity score means that the title is more popular, so the most popular titles will be first.
     * RANKING
     *      Sort results based on specified ranking algorithm. For the advancedTitleSearch query, exactly one ranked title list
     *      constraint must be specified for using this sort option.
     *      ASC: Higher ranks will be first.
     * RELEASE_DATE
     *      Earliest wide release date of a title. Titles without a release date are
     *      placed at the end when using ASC sort order.
     *      ASC: Earlier (older) released title will be first.
     * RUNTIME
     *      The length of the title in terms of runtime.
     *      ASC: Lower runtime means the title is shorter, so shortest titles will be first.
     * TITLE_REGIONAL
     *      Alphabetical sorting based on regional title text as determined by user language preferences.
     *      Language preference is determined by x-imdb-user-country and x-imdb-user-language headers.
     *      Only supports the languages/regions we support for localized search. Defaults to original title otherwise.
     *      ASC: Lower numbers and letters near the top of the alphabet will be returned first.
     * USER_RATING
     *      Weighted IMDb Star Rating as determined by users
     *      Note: IMDb maintains a threshold to a minimum number of ratings before it is considered.
     *      ASC: Lower star rating means the title is rated more poorly, so titles with worse ratings will be first.
     * USER_RATING_COUNT
     *      Count of ratings given by users
     *      Note: IMDb maintains a threshold to a minimum number of ratings before it is considered.
     *      ASC: Lower count of ratings means the title has been rated a fewer number of
     *      times, so titles with least ratings will be first.
     * YEAR
     *      The recognized year of the title. Typically, the release year, but guidelines are here:
     *      https://help.imdb.com/article/contribution/titles/title-formatting/G56U5ERK7YY47CQB
     *      ASC: Earlier (older) titles will be first.
     */
    public $sortBy = "POPULARITY";

    /**
     * TitleSearchAdvanced Sort order options
     * @var string
     * Possible values:
     * ASC
     *      Ascending order e.g. 1,2,3
     * DESC
     *      Descending order e.g. 3,2,1
     */
    public $sortOrder = "ASC";


    #========================================================[ NameSearchAdvanced options ]===
    /**
     * Amount of search results
     * @note should be 250 or less
     * @var int
     */
    public $nameSearchAdvancedAmount = 250;

    /**
     * NameSearchAdvanced sort by options
     * @note default: POPULARITY
     * @var string
     * Possible values:
     * BIRTH_DATE
     *      Sort names based on their birth date
     *      ASC: Earliest dates to Highest dates (Oldest - Youngest)
     * DEATH_DATE
     *      Sort names based on their death date
     *      ASC: Earliest dates to last dates (Died First - Died Last)
     * NAME
     *      Sort Names alphabetically
     *      ASC: A-Z
     * POPULARITY
     *      Sort Names based on their starMeterCurrentWeekRank
     *      ASC: Lower popularity score means that the name is more popular, so the most popular names will be first.
     */
    public $nameSortBy = "POPULARITY";

    /**
     * NameSearchAdvanced sort order options
     * @note default: ASC
     * @var string
     * Possible values:
     * ASC
     *      Ascending order e.g. 1,2,3
     * DESC
     *      Descending order e.g. 3,2,1
     */
    public $nameSortOrder = "ASC";


    #========================================================[ comingSoonStreaming options ]===
    /**
     * Sort by options
     * @note Default: LIST_ORDER
     * @var string
     * Possible values:
     *      LIST_ORDER (Sort titles based on their list order)
     *      CREATED_DATE (Sort titles based on their created date)
     *      MODIFIED_DATE (Sort titles based on their modified date)
     *      POPULARITY
     */
    public $streamSortBy = "LIST_ORDER";

    /**
     * Sort order options
     * @note Default: ASC
     * @var string
     * Possible values:
     *      ASC (Ascending order e.g. 1,2,3)
     *      DESC (Descending order e.g. 3,2,1)
     */
    public $streamSortOrder = "ASC";

    #========================================================[ Thumbnail options ]===

    #---------------------------------------[Title and TitleCombined class Photo thumbnail]---
    /**
     * photo() thumbnail width in pixels
     * @note Default value: 190
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $photoThumbnailWidth = 190;

    /**
     * photo() thumbnail height in pixels
     * @note Default value: 281
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $photoThumbnailHeight = 281;

    #----------------------------------------[Title class Recommendation thumbnail]---
    /**
     * recommendation() thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $recommendationThumbnailWidth = 140;

    /**
     * recommendation() thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $recommendationThumbnailHeight = 207;

    #----------------------------------------[Title class Cast thumbnail]---
    /**
     * cast() thumbnail width in pixels
     * @note Default value: 32
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $castThumbnailWidth = 32;

    /**
     * cast() thumbnail height in pixels
     * @note Default value: 44
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $castThumbnailHeight = 44;

    #----------------------------------------[Title class Episodes thumbnail]---
    /**
     * episode() thumbnail width in pixels
     * @note Default value: 224
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $episodeThumbnailWidth = 224;

    /**
     * episode() thumbnail height
     * @note Default value: 126
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $episodeThumbnailHeight = 126;

    #----------------------------------------[Title and Name class mainphoto thumbnail]---
    /**
     * mainphoto() thumbnail height in pixels
     * @note Default value: 100
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $mainphotoThumbnailHeight = 100;

    #---------------------------------------[Name class Photo thumbnail]---
    /**
     * photo() thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $namePhotoThumbnailWidth = 140;

    /**
     * photo() thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $namePhotoThumbnailHeight = 207;

    #---------------------------------------[Trailers and Chart class Photo thumbnail]---
    /**
     * ALL methods thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $thumbnailWidth = 140;

    /**
     * ALL methods thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $thumbnailHeight = 207;

    #---------------------------------------[Calendar class thumbnail]---
    /**
     * All methods thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $calendarThumbnailWidth = 140;

    /**
     * All methods thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $calendarThumbnailHeight = 207;

    #---------------------------------------[NameSearchAdvanced class thumbnail]---
    /**
     * All methods thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $nameSearchAdvancedThumbnailWidth = 140;

    /**
     * All methods thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $nameSearchAdvancedThumbnailHeight = 207;

    #---------------------------------------[TitleSearchAdvanced class thumbnail]---
    /**
     * All methods thumbnail width in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $titleSearchAdvancedThumbnailWidth = 140;

    /**
     * All methods thumbnail height in pixels
     * @note Default value: 207
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $titleSearchAdvancedThumbnailHeight = 207;

    #----------------------------------------[Title class Video thumbnail]---
    /**
     * video() thumbnail width in pixels
     * @note Default value: 250
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $videoThumbnailWidth = 250;

    /**
     * video() thumbnail height in pixels
     * @note Default value: 140
     * @note Keep ratio in mind, square thumbnails don't work
     * @var int
     */
    public $videoThumbnailHeight = 140;

    #========================================================[ Title class video() options ]===
    /**
     * This will include or exclude mature content video urls
     * @note Default: true
     * @var bool
     */
    public $videoIncludeMature = true;

    /**
     * include all video content types or only trailers
     * @note Default: trailer
     * @var string
     * Possible values:
     *      all (include all video content types)
     *      trailer (include only trailers)
     */
    public $videoContentType = 'trailer';

    #========================================================[ Debug / Expert options ]===
    /**
     * Enable debug mode?
     * @note Default: false
     * @var bool
     */
    public $debug = false;

    /**
     * Throw exceptions when a request to fetch some content fails?
     * @note true: throws exception, false: returns empty object
     * @var bool
     */
    public $throwHttpExceptions = true;
    
    /**
     * Set curlopt_timout (in seconds), this is the time out if curl has a connection problem
     * @note Default: 30
     * @var int
     */
    public $curloptTimeout = 30;

}

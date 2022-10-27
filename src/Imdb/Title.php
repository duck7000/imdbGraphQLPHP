<?php
#############################################################################
# IMDBPHP                              (c) Giorgos Giagas & Itzchak Rehberg #
# written by Giorgos Giagas                                                 #
# extended & maintained by Itzchak Rehberg <izzysoft AT qumran DOT org>     #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * A title on IMDb
 * @author Georgos Giagas
 * @author Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class Title extends MdbBase
{

    const MOVIE = 'Movie';
    const TV_SERIES = 'TV Series';
    const TV_EPISODE = 'TV Episode';
    const TV_MINI_SERIES = 'TV Mini Series';
    const TV_MOVIE = 'TV Movie';
    const TV_SPECIAL = 'TV Special';
    const TV_SHORT = 'TV Short';
    const GAME = 'Video Game';
    const VIDEO = 'Video';
    const SHORT = 'Short';

    protected $akas = array();
    protected $countries = array();
    protected $castlist = array(); // pilot only
    protected $credits_cast = array();
    protected $credits_composer = array();
    protected $credits_director = array();
    protected $credits_producer = array();
    protected $credits_writing = array();
    protected $langs = array();
    protected $langs_full = array();
    protected $all_keywords = array();
    protected $main_poster = "";
    protected $main_poster_thumb = "";
    protected $main_plotoutline = "";
    protected $main_runtime = "";
    protected $main_movietype = "";
    protected $main_title = "";
    protected $main_year = -1;
    protected $main_endyear = -1;
    protected $main_top250 = -1;
    protected $moviegenres = array();
    protected $moviequotes = array();
    protected $movierecommendations = array();
    protected $movieruntimes = array();
    protected $mpaas = array();
    protected $plot_plot = array();
    protected $seasoncount = -1;
    protected $season_episodes = array();
    protected $soundtracks = array();
    protected $split_plot = array();
    protected $split_moviequotes = array();
    protected $taglines = array();
    protected $trivia = array();
    protected $locations = array();
    protected $moviealternateversions = array();
    protected $isSerial = null;
    protected $episodeSeason = null;
    protected $episodeEpisode = null;
    protected $jsonLD = null;
    protected $XmlNextJson = null;

    protected $pageUrls = array(
        "AlternateVersions" => '/alternateversions',
        "Credits" => "/fullcredits",
        "Episodes" => "/episodes",
        "Keywords" => "/keywords",
        "Locations" => "/locations",
        "ParentalGuide" => "/parentalguide",
        "Plot" => "/plotsummary",
        "Quotes" => "/quotes",
        "ReleaseInfo" => "/releaseinfo",
        "Soundtrack" => "/soundtrack",
        "Taglines" => "/taglines",
        "Technical" => "/technical",
        "Title" => "/",
        "Trivia" => "/trivia",
    );

    /**
     * Create an imdb object populated with id, title, year, and movie type
     * @param string $id imdb ID
     * @param string $title film title
     * @param int $year
     * @param string $type
     * @param Config $config
     * @return Title
     */
    public static function fromSearchResult(
        $id,
        $title,
        $year,
        $type,
        Config $config = null
    ) {
        $imdb = new Title($id, $config);
        $imdb->main_title = $title;
        $imdb->main_year = (int)$year;
        $imdb->main_movietype = $type;
        return $imdb;
    }

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     */
    public function __construct(
        $id,
        Config $config = null
    ) {
        parent::__construct($config);
        $this->setid($id);
    }

    #-------------------------------------------------------------[ Open Page ]---

    protected function buildUrl($page = null)
    {
        return "https://" . $this->imdbsite . "/title/tt" . $this->imdbID . $this->getUrlSuffix($page);
    }

    /**
     * @param string $pageName internal name of the page
     * @return string
     */
    protected function getUrlSuffix($pageName)
    {
        if (isset($this->pageUrls[$pageName])) {
            return $this->pageUrls[$pageName];
        }

        if (preg_match('!^Episodes-(-?\d+)$!', $pageName, $match)) {
            if (strlen($match[1]) == 4) {
                return '/episodes?year=' . $match[1];
            } else {
                return '/episodes?season=' . $match[1];
            }
        }

        throw new \Exception("Could not find URL for page $pageName");
    }

    /**
     * Get the URL for this title's page
     * @return string
     */
    public function main_url()
    {
        return "https://" . $this->imdbsite . "/title/tt" . $this->imdbid() . "/";
    }

    /**
     * Setup title and year properties
     */
    protected function title_year()
    {
        $this->getPage("Title");
        if (@preg_match('!<title>(IMDb\s*-\s*)?(?<ititle>.*)(\s*-\s*IMDb)?</title>!', $this->page["Title"], $imatch)) {
            $ititle = $imatch['ititle'];
            if (preg_match('!(?<title>.*) \((?<movietype>.*)(?<year>\d{4}|\?{4})((&nbsp;|–)(?<endyear>\d{4}|)).*\)(.*)!',
                $ititle, $match)) { // serial
                $this->main_movietype = trim($match['movietype']);
                $this->main_year = $match['year'];
                $this->main_endyear = $match['endyear'] ? $match['endyear'] : '0';
                $this->main_title = htmlspecialchars_decode($match['title'], ENT_QUOTES);
            } elseif (preg_match('!(?<title>.*) \((?<movietype>.*)(?<year>\d{4}|\?{4}).*\)(.*)!', $ititle, $match)) {
                $this->main_movietype = trim($match['movietype']);
                $this->main_year = $match['year'];
                $this->main_endyear = $match['year'];
                $this->main_title = htmlspecialchars_decode($match['title'], ENT_QUOTES);
            } elseif (preg_match('!(?<title>.*) \((?<movietype>.*)\)(.*)!', $ititle,
                $match)) { // not yet released, but have been given a movietype.
                $this->main_movietype = trim($match['movietype']);
                $this->main_title = htmlspecialchars_decode($match['title'], ENT_QUOTES);
                $this->main_year = '0';
                $this->main_endyear = '0';
            } elseif (preg_match('!<title>(?<title>.*) - IMDb</title>!', $this->page["Title"],
                $match)) { // not yet released, so no dates etc.
                $this->main_title = htmlspecialchars_decode($match['title'], ENT_QUOTES);
                $this->main_year = '0';
                $this->main_endyear = '0';
            }
            if ($this->main_year == "????") {
                $this->main_year = "";
            }
        }
    }

    /** Get movie type
     * @return string movietype (TV Series, Movie, TV Episode, TV Special, TV Movie, TV Mini-Series, Video Game, TV Short, Video)
     * @see IMDB page / (TitlePage)
     * @brief This is faster than movietypes() as it is retrieved already together with the title.
     *        If no movietype had been defined explicitly, it returns 'Movie' -- so this is always set.
     */
    public function movietype()
    {
        if (empty($this->main_movietype)) {
            if (empty($this->main_title)) {
                $this->title_year();
            } // Most types are shown in the <title> tag
            if (!empty($this->main_movietype)) {
                return $this->main_movietype;
            }
            // TV Special isn't shown in the page title but is mentioned next to the release date
            if (preg_match('/title="See more release dates" >TV Special/', $this->getPage("Title"), $match)) {
                $this->main_movietype = 'TV Special';
            }
            if (empty($this->main_movietype)) {
                $this->main_movietype = 'Movie';
            }
        }
        return $this->main_movietype;
    }

    /** Get movie title
     * @return string title movie title (name)
     * @see IMDB page / (TitlePage)
     */
    public function title()
    {
        if ($this->main_title == "") {
            $this->title_year();
        }
        return $this->main_title;
    }

    /** Get year
     * @return string year
     * @see IMDB page / (TitlePage)
     */
    public function year()
    {
        if ($this->main_year == -1) {
            $this->title_year();
        }
        return $this->main_year;
    }

    /** Get end-year
     *  Usually this returns the same value as year() -- except for those cases where production spanned multiple years, usually for series
     * @return string year
     * @see IMDB page / (TitlePage)
     */
    public function endyear()
    {
        if ($this->main_endyear == -1) {
            $this->title_year();
        }
        return $this->main_endyear;
    }

    #---------------------------------------------------------------[ Runtime ]---

    /**
     * Get general runtime
     * @return string runtime complete runtime string, e.g. "150 min / USA:153 min (director's cut)"
     */
    protected function runtime_all()
    {
        if ($this->main_runtime == "") {
            $this->getPage("Title");
            if (@preg_match('!Runtime:</h4>\s*(.+?)\s*</div!ms', $this->page["Title"], $match)) {
                $this->main_runtime = $match[1];
            }
        }
        if ($this->main_runtime == "") {
            $this->getPage("Technical");
            if (@preg_match('!Runtime.*?<td>(.+?)</td!ms', $this->page["Technical"], $match)) {
                $this->main_runtime = $match[1];
            }
        }
        return $this->main_runtime;
    }

    /**
     * Retrieve all runtimes and their descriptions
     * @return array runtimes (array[0..n] of array[time,annotations]) where annotations is an array of comments meant to describe this cut
     * @see IMDB page / (TitlePage)
     */
    public function runtimes()
    {
        if (empty($this->movieruntimes)) {
            $this->movieruntimes = array();
            $rt = $this->runtime_all();
            foreach (preg_split('!(\||<br>)!', strip_tags($rt, '<br>')) as $runtimestring) {
                if (preg_match_all('/(\d+\s+hr\s+\d+\s+min)? ?\((\d+)\s+min\)|(\d+)\s+min/', trim($runtimestring),
                    $matches,
                    PREG_SET_ORDER, 0)) {
                    $runtime = (!empty($matches[1][2]) ? $matches[1][2] : (!empty($matches[0][2]) ? $matches[0][2] : (!empty($matches[0][3]) ? $matches[0][3] : 0)));
                    $annotations = array();
                    if (preg_match_all("/\((?!\d+\s+min)(.+?)\)/", trim($runtimestring), $matches)) {
                        $annotations = $matches[1];
                    }
                    $this->movieruntimes[] = array(
                        "time" => $runtime,
                        "country" => '',
                        "comment" => '',
                        "annotations" => $annotations
                    );
                }
            }
        }
        return $this->movieruntimes;
    }

    #----------------------------------------------------------[ Movie Rating ]---

    /**
     * Get movie rating
     * @return float|string rating current rating as given by IMDB site
     * @see IMDB page / (TitlePage)
     */
    public function rating()
    {
        return isset($this->jsonLD()->aggregateRating->ratingValue) ? $this->jsonLD()->aggregateRating->ratingValue : '';
    }

    /**
     * Rating out of 100 on metacritic
     * @return int|null
     */
    public function metacriticRating()
    {
        $xpath = $this->getXpathPage("Title");
        $extract = $xpath->query("//span[@class='score-meta']");
        if ($extract && $extract->item(0) != null) {
            return intval(trim($extract->item(0)->nodeValue));
        }
        return null;
    }

    #-------------------------------------------------------[ Recommendations ]---

    /**
     * Get recommended movies (People who liked this...also liked)
     * @return array recommendations (array[title,imdbid,rating,img])
     * @see IMDB page / (TitlePage)
     */
    public function movie_recommendations()
    {
        if (empty($this->movierecommendations)) {
            $xp = $this->getXpathPage("Title");
            $cells = $xp->query("//div[contains(@class, 'ipc-poster-card ipc-poster-card--base')]");
            /** @var \DOMElement $cell */
            foreach ($cells as $key => $cell) {
                $movie = array();
                $get_link_and_name = $xp->query(".//a[contains(@class, 'ipc-poster-card__title')]", $cell);
                if (!empty($get_link_and_name) && preg_match('!tt(\d+)!',
                        $get_link_and_name->item(0)->getAttribute('href'), $ref)) {
                    $movie['title'] = trim($get_link_and_name->item(0)->nodeValue);
                    $movie['imdbid'] = $ref[1];
                    $get_rating = $xp->query(".//span[contains(@class, 'ipc-rating-star--imdb')]", $cell);
                    if (!empty($get_rating->item(0))) {
                        $movie['rating'] = trim($get_rating->item(0)->nodeValue);
                    } else {
                        $movie['rating'] = -1;
                    }
                    $getImage = $xp->query(".//div[contains(@class, 'ipc-media ipc-media--poster')]//img", $cell);
                    if (!empty($getImage->item(0)) && !empty($getImage->item(0)->getAttribute('src'))) {
                        $movie['img'] = $getImage->item(0)->getAttribute('src');
                    } else {
                        $movie['img'] = "";
                    }
                    $this->movierecommendations[] = $movie;
                }
            }
        }
        return $this->movierecommendations;
    }

    #--------------------------------------------------------[ Language Stuff ]---

    /** Get all languages this movie is available in
     * @return array languages (array[0..n] of strings)
     * @see IMDB page / (TitlePage)
     */
    public function languages()
    {
        if (empty($this->langs)) {
            if (preg_match_all('!href="/search/title\?.+?primary_language=([^&]*)[^>]*>\s*(.*?)\s*</a>(\s+\((.*?)\)|)!m',
                $this->getPage("Title"), $matches)) {
                $this->langs = $matches[2];
                $mc = count($matches[2]);
                for ($i = 0; $i < $mc; $i++) {
                    $this->langs_full[] = array(
                        'name' => $matches[2][$i],
                        'code' => $matches[1][$i],
                        'comment' => trim($matches[4][$i])
                    );
                }
            }
        }
        return $this->langs;
    }


    #--------------------------------------------------------------[ Genre(s) ]---

    /** Get all genres the movie is registered for
     * @return array genres (array[0..n] of strings)
     * @see IMDB page / (TitlePage)
     */
    public function genres()
    {
        if (empty($this->moviegenres)) {
            $xpath = $this->getXpathPage("Title");
            $extract_genres = $xpath->query("//li[@data-testid='storyline-genres']//li[@class='ipc-inline-list__item']/a");
            $genres = array();
            foreach ($extract_genres as $genre) {
                if (!empty($genre->nodeValue)) {
                    $genres[] = trim($genre->nodeValue);
                }
            }
            if (count($genres) > 0) {
                $this->moviegenres = $genres;
            }
        }
        if (empty($this->moviegenres)) {
            $genres = isset($this->jsonLD()->genre) ? $this->jsonLD()->genre : array();
            if (!is_array($genres)) {
                $genres = (array)$genres;
            }
            $this->moviegenres = $genres;
        }
        if (empty($this->moviegenres)) {
            if (@preg_match('!Genres:</h4>(.*?)</div!ims', $this->page["Title"], $match)) {
                if (@preg_match_all('!href="[^>]+?>\s*(.*?)\s*<!', $match[1], $matches)) {
                    $this->moviegenres = $matches[1];
                }
            }
        }
        return $this->moviegenres;
    }

    #---------------------------------------------------------------[ Creator ]---

    /**
     * Get the creator(s) of a TV Show
     * @return array creator (array[0..n] of array[name,imdb])
     * @see IMDB page / (TitlePage)
     */
    public function creator()
    {
        $result = array();
        if ($this->jsonLD()->{'@type'} === 'TVSeries' && isset($this->jsonLD()->creator) && is_array($this->jsonLD()->creator)) {
            foreach ($this->jsonLD()->creator as $creator) {
                if ($creator->{'@type'} === 'Person') {
                    $result[] = array(
                        'name' => $creator->name,
                        'imdb' => rtrim(str_replace('/name/nm', '', $creator->url), '/')
                    );
                }
            }
        }
        return $result;
    }

    #---------------------------------------------------------------[ Seasons ]---

    /** Get the number of seasons or 0 if not a series (Test if something is a series first with Title::is_serial())
     * @return int seasons number of seasons
     * @see IMDB page / (TitlePage)
     */
    public function seasons()
    {
        if ($this->seasoncount == -1) {
            $xpath = $this->getXpathPage("Title");
            $dom_xpath_result = $xpath->query('//select[@id="browse-episodes-season"]//option');
            $this->seasoncount = 0;
            foreach ($dom_xpath_result as $xnode) {
                if (!empty($xnode->getAttribute('value')) && intval($xnode->getAttribute('value')) > $this->seasoncount) {
                    $this->seasoncount = intval($xnode->getAttribute('value'));
                }
            }

            if ($this->seasoncount === 0) {
                // Single season shows have a link rather than a select box
                if (preg_match('|href="/title/tt\d{7,8}/episodes\?season=\d+|i', $this->getPage("Title"))) {
                    $this->seasoncount = 1;
                }
            }
        }
        return $this->seasoncount;
    }

    /**
     * Is this title serialised (a tv show)?
     * This could be the show page or an episode
     * @return boolean
     * @see IMDB page / (TitlePage)
     */
    public function is_serial()
    {
        if (isset($this->isSerial)) {
            return $this->isSerial;
        }

        return $this->isSerial = (bool)preg_match('|href="/title/tt\d{7,8}/episodes\?|i', $this->getPage("Title"));
    }

    /**
     * Is this title a TV Show episode?
     * @return boolean
     */
    public function isEpisode()
    {
        return $this->movietype() === self::TV_EPISODE;
    }

    /**
     * Title of the episode
     * @return string
     */
    public function episodeTitle()
    {
        if (!$this->isEpisode()) {
            return "";
        }

        return $this->jsonLD()->name;
    }

    private function populateEpisodeSeasonEpisode()
    {
        if (!isset($this->episodeEpisode) || !isset($this->episodeSeason)) {
            $xpath = $this->getXpathPage("Title");
            $extract = $xpath->query("//div[@data-testid='hero-subnav-bar-season-episode-numbers-section']");
            if ($extract && $extract->item(0) != null) {
                if (false !== preg_match("/S(\d+).+E(\d+)/", $extract->item(0)->textContent, $matches)) {
                    $this->episodeSeason = $matches[1];
                    $this->episodeEpisode = $matches[2];
                }
            } else {
                $this->episodeSeason = 0;
                $this->episodeEpisode = 0;
            }
        }
    }

    /**
     * @return int 0 if not available
     */
    public function episodeSeason()
    {
        if (!$this->isEpisode()) {
            return 0;
        }

        $this->populateEpisodeSeasonEpisode();

        return $this->episodeSeason;
    }

    /**
     * @return int 0 if not available
     */
    public function episodeEpisode()
    {
        if (!$this->isEpisode()) {
            return 0;
        }

        $this->populateEpisodeSeasonEpisode();

        return $this->episodeEpisode;
    }

    /**
     * The date when this episode aired for the first time
     * @return string An ISO 8601 date e.g. 2015-01-01. Will be an empty string if not available
     */
    public function episodeAirDate()
    {
        if (!$this->isEpisode()) {
            return "";
        }

        if (!isset($this->jsonLD()->datePublished)) {
            return '';
        }

        return $this->jsonLD()->datePublished;
    }

    /**
     * Extra information about this episode (if this title is an episode)
     * @return array [imdbid,seriestitle,episodetitle,season,episode,airdate]
     * e.g.
     * <pre>
     * array (
     * 'imdbid'       => '0303461',      // ImdbID of the show
     * 'seriestitle'  => 'Firefly',      // Title of the show
     * 'episodetitle' => 'The Train Job',// Title of this episode
     * 'season'       => 1,
     * 'episode'      => 1,
     * 'airdate'      => '2002-09-20',
     * )
     * </pre>
     * @see IMDB page / (TitlePage)
     */
    public function get_episode_details()
    {
        if (!$this->isEpisode()) {
            return array();
        }

        /* @var $element \DomElement */
        $element = $this->getXpathPage("Title")->query("//a[@data-testid='hero-title-block__series-link']")->item(0);
        if (!empty($element)) {
            preg_match("/(?:nm|tt)(\d{7,8})/", $element->getAttribute("href"), $matches);
            return array(
                "imdbid" => $matches[1],
                "seriestitle" => trim($element->textContent),
                "episodetitle" => $this->episodeTitle(),
                "season" => $this->episodeSeason(),
                "episode" => $this->episodeEpisode(),
                "airdate" => $this->episodeAirDate()
            );
        } else {
            return array(); // no success
        }
    }

    #--------------------------------------------------------[ Plot (Outline) ]---

    /** Get the main Plot outline for the movie
     * @param boolean $fallback Fallback to storyline if we could not catch plotoutline
     * @return string plotoutline
     * @see IMDB page / (TitlePage)
     */
    public function plotoutline()
    {
        if ($this->main_plotoutline == "") {
            if (isset($this->jsonLD()->description)) {
                $this->main_plotoutline = htmlspecialchars_decode($this->jsonLD()->description, ENT_QUOTES | ENT_HTML5);
            } else {
                $page = $this->getPage("Title");
                if (preg_match('!class="summary_text">\s*(.*?)\s*</div>!ims', $page, $match)) {
                    $this->main_plotoutline = trim($match[1]);
                }
            }

        }
        $this->main_plotoutline = preg_replace('!\s*<a href="/title/tt\d{7,8}/(plotsummary|synopsis)[^>]*>See full (summary|synopsis).*$!i',
            '', $this->main_plotoutline);
        $this->main_plotoutline = preg_replace('#<a href="[^"]+"\s+>Add a Plot</a>&nbsp;&raquo;#', '',
            $this->main_plotoutline);
        return $this->main_plotoutline;
    }

    #--------------------------------------------------------[ Photo specific ]---

    /**
     * Setup cover photo (thumbnail and big variant)
     * @see IMDB page / (TitlePage)
     */
    private function populatePoster()
    {
        if (isset($this->jsonLD()->image)) {
            $this->main_poster = $this->jsonLD()->image;
        }
        if (preg_match('!<img [^>]+title="[^"]+Poster"[^>]+src="([^"]+)"[^>]+/>!ims', $this->getPage("Title"), $match)
            && !empty($match[1])) {
            $this->main_poster_thumb = $match[1];
        } else {
            $xpath = $this->getXpathPage("Title");
            $thumb = $xpath->query("//div[contains(@class, 'ipc-poster ipc-poster--baseAlt') and contains(@data-testid, 'hero-media__poster')]//img");
            if (!empty($thumb) && $thumb->item(0) != null) {
                $this->main_poster_thumb = $thumb->item(0)->getAttribute('src');
            }
        }
    }


    /**
     * Get the poster/cover image URL
     * @param boolean $thumb get the thumbnail (182x268) or the full sized image
     * @return string|false photo (string URL if found, FALSE otherwise)
     * @see IMDB page / (TitlePage)
     */
    public function photo($thumb = true)
    {
        if (empty($this->main_poster)) {
            $this->populatePoster();
        }
        if (!$thumb && empty($this->main_poster)) {
            return false;
        }
        if ($thumb && empty($this->main_poster_thumb)) {
            return false;
        }
        if ($thumb) {
            return $this->main_poster_thumb;
        }
        return $this->main_poster;
    }

    /**
     * Save the poster/cover image to disk
     * @param string $path where to store the file
     * @param boolean $thumb get the thumbnail (100x140, default) or the
     *        bigger variant (400x600 - FALSE)
     * @return boolean success
     * @see IMDB page / (TitlePage)
     */
    public function savephoto($path, $thumb = true)
    {
        $photo_url = $this->photo($thumb);
        if (!$photo_url) {
            return false;
        }

        $req = new Request($photo_url, $this->config);
        $req->sendRequest();
        if (strpos($req->getResponseHeader("Content-Type"), 'image/jpeg') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/gif') === 0 ||
            strpos($req->getResponseHeader("Content-Type"), 'image/bmp') === 0) {
            $image = $req->getResponseBody();
        } else {
            $ctype = $req->getResponseHeader("Content-Type");
            $this->debug_scalar("*photoerror* at " . __FILE__ . " line " . __LINE__ . ": " . $photo_url . ": Content Type is '$ctype'");
            if (substr($ctype, 0, 4) == 'text') {
                $this->debug_scalar("Details: <PRE>" . $req->getResponseBody() . "</PRE>\n");
            }
            return false;
        }

        $fp2 = fopen($path, "w");
        if (!$fp2) {
            return false;
        }
        fputs($fp2, $image);
        return true;
    }

    #-------------------------------------------------[ Country of Production ]---

    /** Get country of production
     * @return array country (array[0..n] of string)
     * @see IMDB page / (TitlePage)
     */
    public function country()
    {
        if (empty($this->countries)) {
            if (preg_match_all('!/search/title\/?\?country_of_origin=[^>]+?>(.*?)<!m', $this->getPage("Title"),
                $matches)) {
                $this->countries = $matches[1];
            }
        }
        return $this->countries;
    }


    #------------------------------------------------------------[ Movie AKAs ]---

    /**
     * Get movie's alternative names
     * Note: This may return an empty country or comments. The original title will have a country of '' and a comment of 'original title'
     * comment, year and lang are there for backwards compatibility and should not be used
     * @return array array[0..n] of array[title,country,comments[]]
     * @see IMDB page ReleaseInfo
     */
    public function alsoknow()
    {
        if (empty($this->akas)) {
            $page = $this->getPage("ReleaseInfo");
            if (empty($page)) {
                return array();
            } // no such page

            $table = Parsing::table($page, "//*[@id=\"akas\"]/following-sibling::table");

            if (empty($table)) {
                return array();
            }

            foreach ($table as $row) {
                $description = $row[0];
                $title = $row[1];

                $firstbracket = strpos($description, '(');
                if ($firstbracket === false) {
                    $country = $description;
                    $comments = array();
                } else {
                    $country = trim(substr($description, 0, $firstbracket));
                    preg_match_all("@\((.+?)\)@", $description, $matches);
                    $comments = $matches[1];
                }

                $this->akas[] = array(
                    "title" => $title,
                    "country" => $country,
                    "comments" => $comments,
                    "comment" => implode(', ', $comments),
                    "year" => '',
                    "lang" => ''
                );
            }
        }
        return $this->akas;
    }

    #-------------------------------------------------------[ MPAA / PG / FSK ]---

    /**
     * Get the MPAA rating / Parental Guidance / Age rating for this title by country
     * @param bool $ratings On false it will return the last rating for each country,
     *                      otherwise return every rating in an array.
     * @return array [country => rating] or [country => [rating,]]
     * @see IMDB Parental Guidance page / (parentalguide)
     */
    public function mpaa($ratings = false)
    {
        if (empty($this->mpaas)) {
            $xpath = $this->getXpathPage("ParentalGuide");
            if (empty($xpath)) {
                return array();
            }
            $cells = $xpath->query("//section[@id=\"certificates\"]//li[@class=\"ipl-inline-list__item\"]");
            foreach ($cells as $cell) {
                if ($a = $cell->getElementsByTagName('a')->item(0)) {
                    $mpaa = explode(':', $a->nodeValue, 2);
                    $country = trim($mpaa[0]);
                    $rating = isset($mpaa[1]) ? $mpaa[1] : '';

                    if ($ratings) {
                        if (!isset($this->mpaas[$country])) {
                            $this->mpaas[$country] = [];
                        }

                        $this->mpaas[$country][] = $rating;
                    } else {
                        $this->mpaas[$country] = $rating;
                    }
                }
            }
        }
        return $this->mpaas;
    }

    #----------------------------------------------[ Position in the "Top250" ]---
    /**
     * Find the position of a movie or tv show in the top 250 ranked movies or tv shows
     * @return int position a number between 1..250 if ranked, 0 otherwise
     * @author abe
     * @see http://projects.izzysoft.de/trac/imdbphp/ticket/117
     */
    public function top250()
    {
        if ($this->main_top250 == -1) {
            $xpath = $this->getXpathPage("Title");
            $topRated = $xpath->query("//a[@data-testid='award_top-rated']")->item(0);
            if ($topRated && preg_match('/#(\d+)/', $topRated->nodeValue, $match)) {
                $this->main_top250 = (int)$match[1];
            } else {
                $this->main_top250 = 0;
            }
        }
        return $this->main_top250;
    }


    #=====================================================[ /plotsummary page ]===
    #--------------------------------------------------[ Full Plot (combined) ]---
    /** Get the movies plot(s)
     * @return array plot (array[0..n] of strings)
     * @see IMDB page /plotsummary
     */
    public function plot()
    {
        if (empty($this->plot_plot)) {
            $xpath = $this->getXpathPage("Plot");
            if (empty($xpath)) {
                return array();
            } // no such page
            $cells = $xpath->query("//ul[@id=\"plot-summaries-content\"]/li[@id!=\"no-summary-content\"]");
            foreach ($cells as $cell) {
                $link = '';
                $anchors = $cell->getElementsByTagName('a');
                if ($a = $anchors->item($anchors->length - 1)) {
                    if (preg_match('!/search/title!i', $a->getAttribute('href'))) {
                        $href = preg_replace(
                            '!/search/title!i',
                            'https://' . $this->imdbsite . '/search/title',
                            $a->getAttribute('href')
                        );
                        $link = "\n-\n" . '<a href="' . $href . '">' . trim($a->nodeValue) . '</a>';
                    }
                }
                $this->plot_plot[] = $cell->getElementsByTagName('p')->item(0)->nodeValue . $link;
            }
        }
        return $this->plot_plot;
    }

    #-----------------------------------------------------[ Full Plot (split) ]---

    /** Get the movie plot(s) - split-up variant
     * @return array array[0..n] of array[string plot,array author] - where author consists of string name and string url
     * @see IMDB page /plotsummary
     */
    public function plot_split()
    {
        if (empty($this->split_plot)) {
            if (empty($this->plot_plot)) {
                $this->plot_plot = $this->plot();
            }
            foreach ($this->plot_plot as $plot) {
                if (preg_match('!(?<plot>.*?)\n-\n<a href="(?<author_url>.*?)">(?<author_name>.*?)<\/a>!ims', $plot,
                    $match)) {
                    $this->split_plot[] = array(
                        "plot" => $match['plot'],
                        "author" => array("name" => $match['author_name'], "url" => $match['author_url'])
                    );
                } else {
                    $this->split_plot[] = array("plot" => $plot, "author" => array("name" => '', "url" => ''));
                }
            }
        }
        return $this->split_plot;
    }

    #========================================================[ /taglines page ]===
    #--------------------------------------------------------[ Taglines Array ]---
    /** Get all available taglines for the movie
     * @return array taglines (array[0..n] of strings)
     * @see IMDB page /taglines
     */
    public function taglines()
    {
        if (empty($this->taglines)) {
            $this->getPage("Taglines");
            if ($this->page["Taglines"] == "cannot open page") {
                return array();
            } // no such page
            if (preg_match_all('!<div class="soda[^>]+>\s*(.*)\s*</div!U', $this->page["Taglines"], $matches)) {
                $this->taglines = array_map('trim', $matches[1]);
            }
        }
        return $this->taglines;
    }

    #=====================================================[ /fullcredits page ]===
    #-----------------------------------------------------[ Helper: TableRows ]---
    /**
     * Get rows for a given table on the page
     * @param string html
     * @param string table_start
     * @return string[] Contents of each row of the table
     * @see used by the methods director, cast, writing, producer, composer
     */
    protected function get_table_rows($html, $table_start)
    {
        if ($table_start == "Writing Credits" || $table_start == "Series Writing Credits") {
            $row_s = strpos($html, ">" . $table_start);
        } else {
            $row_s = strpos($html, ">" . $table_start . "&nbsp;<");
        }
        if ($row_s == 0) {
            return array();
        }
        $endtable = strpos($html, "</table>", $row_s);
        $block = substr($html, $row_s, $endtable - $row_s);
        if (preg_match_all('!<tr>(.+?)</tr>!ims', $block, $matches)) {
            $rows = $matches[1];
        }
        return $rows;
    }

    #------------------------------------------------[ Helper: Cast TableRows ]---

    /** Get rows for the cast table on the page
     * @param string html
     * @param string table_start
     * @return array array[0..n] of strings
     * @see used by the method cast
     */
    protected function get_table_rows_cast($html, $table_start, $class = "nm")
    {
        $row_s = strpos($html, '<table class="cast_list">');
        if ($row_s == 0) {
            return array();
        }
        $endtable = strpos($html, "</table>", $row_s);
        $block = substr($html, $row_s, $endtable - $row_s);
        if (preg_match_all('!<tr.*?>(.*?)</tr>!ims', $block, $matches)) {
            return $matches[1];
        }
        return array();
    }

    #------------------------------------------------------[ Helper: RowCells ]---

    /** Get content of table row cells
     * @param string row (as returned by imdb::get_table_rows)
     * @return array cells (array[0..n] of strings)
     * @see used by the methods director, cast, writing, producer, composer
     */
    protected function get_row_cels($row)
    {
        if (preg_match_all("/<td.*?>(.*?)<\/td>/ims", $row, $matches)) {
            return $matches[1];
        }
        return array();
    }

    #-------------------------------------------[ Helper: Get IMDBID from URL ]---

    /** Get the IMDB ID from a names URL
     * @param string href url to the staff members IMDB page
     * @return string IMDBID of the staff member
     * @see used by the methods director, cast, writing, producer, composer
     */
    protected function get_imdbname($href)
    {
        return preg_replace('!^.*nm(\d+).*$!ims', '$1', $href);
    }

    #-------------------------------------------------------------[ Directors ]---

    /**
     * Get the director(s) of the movie
     * @return array director (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function director()
    {
        if (!empty($this->credits_director)) {
            return $this->credits_director;
        }
        $directorRows = $this->get_table_rows($this->getPage('Credits'), "Directed by");
        if (!$directorRows) {
            $directorRows = $this->get_table_rows($this->getPage('Credits'), "Series Directed by");
        }
        foreach ($directorRows as $directorRow) {
            $cells = $this->get_row_cels($directorRow);
            if (isset($cells[0])) {
                if (isset($cells[2])) {
                    $role = trim(strip_tags($cells[2]));
                } else {
                    $role = null;
                }

                $this->credits_director[] = array(
                    'imdb' => $this->get_imdbname($cells[0]),
                    'name' => trim(strip_tags($cells[0])),
                    'role' => $role ?: null
                );
            }
        }
        return $this->credits_director;
    }

    #----------------------------------------------------------------[ Actors ]---

    /*
    * Get the Stars members for this title
    * @return empty array OR array Stars (array[0..n] of array[imdb,name])
     * e.g.
     * <pre>
     * array (
     *  'imdb' => '0000134',
     *  'name' => 'Robert De Niro', // Actor's name on imdb
     * )
     * </pre>
    */
    public function actor_stars()
    {
        $stars = array();
        if (empty($this->jsonLD()->actor)) {
            return $stars;
        }
        $actors = $this->jsonLD()->actor;
        if (!is_array($this->jsonLD()->actor)) {
            $actors = array($this->jsonLD()->actor);
        }
        foreach ($actors as $actor) {
            $act = array(
                'imdb' => preg_replace('!.*?/name/nm(\d+)/.*!', '$1', $actor->url),
                'name' => $actor->name,
            );
            $stars[] = $act;
        }
        return $stars;
    }

    /**
     * Get the actors/cast members for this title
     * @return array cast (array[0..n] of array[imdb,name,name_alias,role,role_episodes,role_start_year,role_end_year,thumb,photo])
     * e.g.
     * <pre>
     * array (
     *  'imdb' => '0922035',
     *  'name' => 'Dominic West', // Actor's name on imdb
     *  'name_alias' => NULL, // Name credited to actor if it is different to their imdb name
     *  'credited' => true, // Was the actor credited in the film?
     *  'role' => "Det. James 'Jimmy' McNulty",
     *  'role_episodes' => 60, // Only applies to episodic titles. Will be NULL if not available
     *  'role_start_year' => 2002, // Only applies to episodic titles. Will be NULL if not available
     *  'role_end_year' => 2008, // Only applies to episodic titles. Will be NULL if not available
     *  'role_other' => array() // Any other information about what the cast member did e.g. 'voice', 'archive footage'
     *  'thumb' => 'https://ia.media-imdb.com/images/M/MV5BMTY5NjQwNDY2OV5BMl5BanBnXkFtZTcwMjI2ODQ1MQ@@._V1_SY44_CR0,0,32,44_AL_.jpg',
     *  'photo' => 'https://ia.media-imdb.com/images/M/MV5BMTY5NjQwNDY2OV5BMl5BanBnXkFtZTcwMjI2ODQ1MQ@@.jpg' // Fullsize image of actor
     * )
     * </pre>
     * @see IMDB page /fullcredits
     */
    public function cast()
    {

        if (!empty($this->credits_cast)) {
            return $this->credits_cast;
        }

        $page = $this->getPage("Credits");

        if (empty($page)) {
            return array(); // no such page
        }

        $cast_rows = $this->get_table_rows_cast($page, "Cast", "itemprop");
        foreach ($cast_rows as $cast_row) {
            $cels = $this->get_row_cels($cast_row);
            if (4 !== count($cels)) {
                continue;
            }
            $dir = array(
                'imdb' => null,
                'name' => null,
                'name_alias' => null,
                'credited' => true,
                'role' => null,
                'role_episodes' => null,
                'role_start_year' => null,
                'role_end_year' => null,
                'role_other' => array(),
                'thumb' => null,
                'photo' => null
            );
            $dir["imdb"] = preg_replace('!.*href="/name/nm(\d+)/.*!ims', '$1', $cels[1]);
            $dir["name"] = trim(strip_tags($cels[1]));
            if (empty($dir['name'])) {
                continue;
            }


            $role_cell = trim(strip_tags(str_replace('&nbsp;', '', $cels[3])));
            if ($role_cell) {
                $role_lines = explode("\n", $role_cell);
                // The first few lines (before any lines starting with brackets) are the role name
                while ($role_line = array_shift($role_lines)) {
                    $role_line = trim($role_line);
                    if (!$role_line) {
                        continue;
                    }
                    if ($role_line[0] == '(' || preg_match('@\d+ episode@', $role_line)) {
                        // Start of additional information, stop looking for the role name
                        array_unshift($role_lines, $role_line);
                        break;
                    }
                    if ($dir['role']) {
                        $dir['role'] .= ' ' . $role_line;
                    } else {
                        $dir['role'] = $role_line;
                    }
                }

                // Trim off the funny / ... role added on tv shows where an actor has multiple characters
                $dir['role'] = str_replace(' / ...', '', $dir['role']);

                $cleaned_role_cell = implode("\n", $role_lines);

                if (preg_match("#\(as (.+?)\)#s", $cleaned_role_cell, $matches)) {
                    $dir['name_alias'] = $matches[1];
                    $cleaned_role_cell = preg_replace("#\(as (.+?)\)#s", '', $cleaned_role_cell);
                }

                if (preg_match("#(\d+) episodes?, (\d+)(?:-(\d+))?#", $cleaned_role_cell, $matches)) {
                    $dir['role_episodes'] = (int)$matches[1];
                    $dir['role_start_year'] = (int)$matches[2];
                    if (isset($matches[3])) {
                        $dir['role_end_year'] = (int)$matches[3];
                    } else {
                        // If no end year, make the same as start year
                        $dir['role_end_year'] = (int)$matches[2];
                    }
                    $cleaned_role_cell = preg_replace("#\((\d+) episodes?, (\d+)(?:-(\d+))?\)#", '',
                        $cleaned_role_cell);
                }

                // Extract uncredited and other bits from their brackets after the role
                if (preg_match_all("#\((.+?)\)#", $cleaned_role_cell, $matches)) {
                    foreach ($matches[1] as $role_info) {
                        $role_info = trim($role_info);
                        if ($role_info == 'uncredited') {
                            $dir['credited'] = false;
                        } else {
                            $dir['role_other'][] = $role_info;
                        }
                    }
                }
            }


            if (preg_match('!.*<img [^>]*loadlate="([^"]+)".*!ims', $cels[0], $match)) {
                $dir["thumb"] = $match[1];
                if (strpos($dir["thumb"], '._V1')) {
                    $dir["photo"] = preg_replace('#\._V1_.+?(\.\w+)$#is', '$1', $dir["thumb"]);
                }
            } else {
                $dir["thumb"] = $dir["photo"] = "";
            }

            $this->credits_cast[] = $dir;
        }
        return $this->credits_cast;
    }


    #---------------------------------------------------------------[ Writers ]---

    /** Get the writer(s)
     * @return array writers (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function writing()
    {
        if (empty($this->credits_writing)) {
            $page = $this->getPage("Credits");
            if (empty($page)) {
                return array(); // no such page
            }
        }
        $writing_rows = $this->get_table_rows($this->page["Credits"], "Writing Credits");
        if (!$writing_rows) {
            $writing_rows = $this->get_table_rows($this->page["Credits"], "Series Writing Credits");
        }
        if (!$writing_rows) {
            return array();
        }
        for ($i = 0; $i < count($writing_rows); $i++) {
            $wrt = array();
            if (preg_match('!<a\s+href="/name/nm(\d+)/[^>]*>\s*(.+)\s*</a>!ims', $writing_rows[$i], $match)) {
                $wrt['imdb'] = $match[1];
                $wrt['name'] = trim($match[2]);
            } elseif (preg_match('!<td\s+class="name">(.+?)</td!ims', $writing_rows[$i], $match)) {
                $wrt['imdb'] = '';
                $wrt['name'] = trim($match[1]);
            } else {
                continue;
            }
            if (preg_match('!<td\s+class="credit"\s*>\s*(.+?)\s*</td>!ims', $writing_rows[$i], $match)) {
                $wrt['role'] = trim($match[1]);
            } else {
                $wrt['role'] = null;
            }
            $this->credits_writing[] = $wrt;
        }
        return $this->credits_writing;
    }

    #-------------------------------------------------------------[ Producers ]---

    /**
     * Obtain the producer(s)
     * @return array producer (array[0..n] of arrays[imdb,name,role])
     * e.g.
     * Array (
     *  'imdb' => '0905152'
     *  'name' => 'Lilly Wachowski'
     *  'role' => 'executive producer' // Can be null if no role is given
     * )
     * @see IMDB page /fullcredits
     */
    public function producer()
    {
        if (!empty($this->credits_producer)) {
            return $this->credits_producer;
        }
        $producerRows = $this->get_table_rows($this->getPage("Credits"), "Produced by");
        if (!$producerRows) {
            $producerRows = $this->get_table_rows($this->getPage("Credits"), "Series Produced by");
        }
        foreach ($producerRows as $producerRow) {
            $cells = $this->get_row_cels($producerRow);
            if (count($cells) > 2) {
                if (isset($cells[2])) {
                    $role = trim(strip_tags($cells[2]));
                    $role = preg_replace('/ \(as .+\)$/', '', $role);
                } else {
                    $role = null;
                }

                $this->credits_producer[] = array(
                    'imdb' => $this->get_imdbname($cells[0]),
                    'name' => trim(strip_tags($cells[0])),
                    'role' => $role ?: null
                );
            }
        }
        return $this->credits_producer;
    }

    #-------------------------------------------------------------[ Composers ]---

    /** Obtain the composer(s) ("Original Music by...")
     * @return array composer (array[0..n] of arrays[imdb,name,role])
     * @see IMDB page /fullcredits
     */
    public function composer()
    {
        if (!empty($this->credits_composer)) {
            return $this->credits_composer;
        }
        $composer_rows = $this->get_table_rows($this->getPage('Credits'), "Music by");
        if (!$composer_rows) {
            $composer_rows = $this->get_table_rows($this->getPage('Credits'), "Series Music by");
        }
        foreach ($composer_rows as $composer_row) {
            $composer = array();
            if (preg_match('!<a\s+href="/name/nm(\d+)/[^>]*>\s*(.+)\s*</a>!ims', $composer_row, $match)) {
                $composer['imdb'] = $match[1];
                $composer['name'] = trim($match[2]);
            } elseif (preg_match('!<td\s+class="name">(.+?)</td!ims', $composer_row, $match)) {
                $composer['imdb'] = '';
                $composer['name'] = trim($match[1]);
            } else {
                continue;
            }
            if (preg_match('!<td\s+class="credit"\s*>\s*(.+?)\s*</td>!ims', $composer_row, $match)) {
                $composer['role'] = trim($match[1]);
            } else {
                $composer['role'] = null;
            }
            $this->credits_composer[] = $composer;
        }
        return $this->credits_composer;
    }

    #========================================================[ /episodes page ]===
    #--------------------------------------------------------[ Episodes Array ]---
    /**
     * Get the series episode(s)
     * @return array episodes (array[0..n] of array[0..m] of array[imdbid,title,airdate,plot,season,episode,image_url])
     * @see IMDB page /episodes
     * @version Attention: Starting with revision 506 (version 2.1.3), the outer array no longer starts at 0 but reflects the real season number!
     */
    public function episodes()
    {
        if (!($this->is_serial() || $this->isEpisode())) {
            return array();
        }

        if (empty($this->season_episodes)) {
            if ($this->isEpisode()) {
                $ser = $this->get_episode_details();
                if (isset($ser['imdbid'])) {
                    $show = new Title($ser['imdbid'], $this->config);
                    return $this->season_episodes = $show->episodes();
                } else {
                    return array();
                }
            }
            $page = $this->getPage("Episodes");
            if (empty($page)) {
                return $this->season_episodes;
            }

            /*
             * There are (sometimes) two select boxes: one per season and one per year.
             * IMDb picks one select to use by default and the other starts with an empty option.
             * The one which starts with a numeric option is the one we need to loop over sometimes the other doesn't work
             * (e.g. a show without seasons might have 100s of episodes in season 1 and its page won't load)
             *
             * default to year based
             */
            $selectId = 'id="byYear"';
            if (preg_match('!<select id="bySeason"(.*?)</select!ims', $page, $matchSeason)) {
                preg_match_all('#<\s*?option\b[^>]*>(.*?)</option\b[^>]*>#s', $matchSeason[1], $matchOptionSeason);
                if (is_numeric(trim($matchOptionSeason[1][0]))) {
                    //season based
                    $selectId = 'id="bySeason"';
                }
            }

            if (preg_match('!<select ' . $selectId . '(.*?)</select!ims', $page, $match)) {
                preg_match_all('!<option\s+(selected="selected" |)value="([^"]+)">!i', $match[1], $matches);
                $count = count($matches[0]);
                for ($i = 0; $i < $count; ++$i) {
                    $s = $matches[2][$i];
                    $page = $this->getPage("Episodes-$s");
                    if (empty($page)) {
                        continue; // no such page
                    }
                    // fetch episodes images
                    preg_match_all('!<div class="image">\s*(?<img>.*?)\s*</div>\s*!ims', $page, $img);
                    $urlIndex = 0;
                    $preg = '!<div class="info" itemprop="episodes".+?>\s*<meta itemprop="episodeNumber" content="(?<episodeNumber>-?\d+)"/>\s*'
                        . '<div class="airdate">\s*(?<airdate>.*?)\s*</div>\s*'
                        . '.+?\shref="/title/tt(?<imdbid>\d{7,8})/[^"]+?"\s+title="(?<title>[^"]+?)"\s+itemprop="name"'
                        . '.+?<div class="item_description" itemprop="description">(?<plot>.*?)</div>!ims';
                    preg_match_all($preg, $page, $eps, PREG_SET_ORDER);
                    foreach ($eps as $ep) {
                        //Fetch episodes image url
                        if (preg_match('/(?<!_)src=([\'"])?(.*?)\\1/', $img['img'][$urlIndex], $foundUrl)) {
                            $image_url = $foundUrl[2];
                        } else {
                            $image_url = "";
                        }
                        $plot = preg_replace('#<a href="[^"]+"\s+>Add a Plot</a>#', '', trim($ep['plot']));
                        $plot = preg_replace('#Know what this is about\?<br>\s*<a href="[^"]+"\s*> Be the first one to add a plot.\s*</a>#ims',
                            '', $plot);

                        $episode = array(
                            'imdbid' => $ep['imdbid'],
                            'title' => trim($ep['title']),
                            'airdate' => $ep['airdate'],
                            'plot' => strip_tags($plot),
                            'season' => (int)$s,
                            'episode' => (int)$ep['episodeNumber'],
                            'image_url' => $image_url
                        );
                        $urlIndex = $urlIndex + 1;

                        if ($ep['episodeNumber'] == -1) {
                            $this->season_episodes[$s][] = $episode;
                        } else {
                            $this->season_episodes[$s][$ep['episodeNumber']] = $episode;
                        }
                    }
                }
            }
        }
        return $this->season_episodes;
    }


    #==========================================================[ /quotes page ]===
    #----------------------------------------------------------[ Quotes Array ]---
    /** Get the quotes for a given movie
     * @return array quotes (array[0..n] of string)
     * @see IMDB page /quotes
     */
    public function quotes()
    {
        if (empty($this->moviequotes)) {
            $page = $this->getPage("Quotes");
            if (empty($page)) {
                return array();
            }

            if (preg_match_all('!<div class="sodatext">\s*(.*?)\s*</div>!ims', str_replace("\n", " ", $page),
                $matches)) {
                foreach ($matches[1] as $match) {
                    $this->moviequotes[] = str_replace('href="/name/', 'href="https://' . $this->imdbsite . '/name/',
                        preg_replace('!<span class="linksoda".+?</span>!ims', '', $match));
                }
            }
        }
        return $this->moviequotes;
    }

    /** Get the quotes for a given movie (split-up variant)
     * @return array quote array[string quote, array character]; character: array[string url, string name]
     * @see IMDB page /quotes
     */
    public function quotes_split()
    {
        if (empty($this->split_moviequotes)) {
            if (empty($this->moviequotes)) {
                $quote = $this->quotes();
            }
            $i = 0;
            if (!empty($this->moviequotes)) {
                foreach ($this->moviequotes as $moviequotes) {
                    if (@preg_match_all('!<p>\s*(.*?)\s*</p>!', $moviequotes, $matches)) {
                        if (!empty($matches[1])) {
                            foreach ($matches[1] as $quote) {
                                if (@preg_match('!href="([^"]*)"\s*>.+?character">(.*?)</span.+?:(.*)!', $quote,
                                    $match)) {
                                    $this->split_moviequotes[$i][] = array(
                                        'quote' => trim(strip_tags($match[3])),
                                        'character' => array('url' => $match[1], 'name' => $match[2])
                                    );
                                } else {
                                    $this->split_moviequotes[$i][] = array(
                                        'quote' => trim(strip_tags($quote)),
                                        'character' => array('url' => '', 'name' => '')
                                    );
                                }
                            }
                        }
                    }
                    ++$i;
                }
            }
        }
        return $this->split_moviequotes;
    }

    #==========================================================[ /trivia page ]===
    #----------------------------------------------------------[ Trivia Array ]---
    /**
     * Get the trivia info
     * @param boolean $spoil if true spoilers are also included.
     * @return array trivia (array[0..n] string
     * @see IMDB page /trivia
     */
    public function trivia($spoil = false)
    {
        if (empty($this->trivia)) {
            $xpath = $this->getXpathPage("Trivia");
            if (empty($xpath)) {
                return array(); // no such page
            }
            if ($xpath->evaluate("//div[contains(@id,'no_content')]")->count()) {
                return array(); // no data available
            }
            if ($triviaContent = $xpath->query("//div[@id='trivia_content']//div[@class='list']")) {
                foreach ($triviaContent as $value) {
                    if ($value->getElementsByTagName('a')->item(0)->getAttribute('id') != "spoilers") {
                        if ($cells = $xpath->query('.//div[contains(@class, "sodatext")]', $value)) {
                            foreach ($cells as $cell) {
                                if ($cell->nodeValue != "") {
                                    $this->trivia[] = trim($cell->nodeValue);
                                }
                            }
                        }
                    } elseif ($spoil == true) {
                        if ($cells = $xpath->query('.//div[contains(@class, "sodatext")]', $value)) {
                            foreach ($cells as $cell) {
                                if ($cell->nodeValue != "") {
                                    $this->trivia[] = trim($cell->nodeValue);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->trivia;
    }


    #======================================================[ /soundtrack page ]===
    #------------------------------------------------------[ Soundtrack Array ]---
    /**
     * Get the soundtrack listing
     * @return array soundtracks
     * [ soundtrack : name of the track
     *   credits : Full text only description of the credits. Contains newline characters
     *   credits_raw : The credits as they are on the imdb page. Contains html with links
     * ]
     * e.g
     * <pre>[
     *   [
     *     'soundtrack' => 'Rock is Dead',
     *     'credits' => 'Written by Marilyn Manson, Jeordie White, and Madonna Wayne Gacy
    Performed by Marilyn Manson
    Courtesy of Nothing/Interscope Records
    Under License from Universal Music Special Markets',
     *     'credits_raw' => 'Written by <a href="/name/nm0001504">Marilyn Manson</a>, <a href="/name/nm0708390">Jeordie White</a>, and <a href="/name/nm0300476">Madonna Wayne Gacy</a> <br />
    Performed by <a href="/name/nm0001504">Marilyn Manson</a> <br />
    Courtesy of Nothing/Interscope Records <br />
    Under License from Universal Music Special Markets <br />',
     *   ]
     * ]</pre>
     * @see IMDB page /soundtrack
     */
    public function soundtrack() {
        if (empty($this->soundtracks)) {
            $page = $this->getPage("Soundtrack");
            if (empty($page)) return array(); // no such page
            if (preg_match_all('!class="soundTrack soda (odd|even)"\s*>\s*(?<title>.+?)<br\s*/>(?<desc>.+?)</div>!ims', $page, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $this->soundtracks[] = array(
                        'soundtrack' => trim($match['title']),
                        'credits' => preg_replace("/\s*\n\s*/", "\n", trim(strip_tags($match['desc']))),
                        'credits_raw' => trim($match['desc'])
                    );
                }
            }
        }
        return $this->soundtracks;
    }

    #=======================================================[ /locations page ]===

    /**
     * Filming locations
     * @return string[]
     * @see IMDB page /locations
     */
    public function locations()
    {
        if (empty($this->locations)) {
            $xpath = $this->getXpathPage("Locations");
            if (empty($xpath)) {
                return array();
            } // no such page
            $cells = $xpath->query("//section[@id=\"filming_locations\"]//dt");
            foreach ($cells as $cell) {
                $this->locations[] = trim($cell->nodeValue);
            }
        }
        return $this->locations;
    }

    #========================================================[ /keywords page ]===
    #--------------------------------------------------------------[ Keywords ]---
    /**
     * Get all keywords from movie
     * @return array keywords
     * @see IMDB page /keywords
     */
    public function keywords_all()
    {
        if (empty($this->all_keywords)) {
            $xpath = $this->getXpathPage("Keywords");
            if ($xpath->evaluate("//div[contains(@id,'no_content')]")->count()) {
                return array();
            }
            if ($cells = $xpath->query("//div[@class=\"sodatext\"]/a")) {
                foreach ($cells as $cell) {
                    if ($cell->nodeValue != "") {
                        $this->all_keywords[] = trim($cell->nodeValue);
                    }
                }
            }
        }
        return $this->all_keywords;
    }

    /**
     * Get the Alternate Versions for a given movie
     * @return array Alternate Version (array[0..n] of string)
     * @see IMDB page /alternateversions
     */
    public function alternateVersions()
    {
        if (empty($this->moviealternateversions)) {
            $xpath = $this->getXpathPage("AlternateVersions");
            if ($xpath->evaluate("//div[contains(@id,'no_content')]")->count()) {
                return array();
            }
            $cells = $xpath->query("//div[@class=\"soda odd\" or @class=\"soda even\"]");
            foreach ($cells as $cell) {
                $output = '';
                $nodes = $xpath->query(".//text()", $cell);
                foreach ($nodes as $node) {
                    if ($node->parentNode->nodeName === 'li') {
                        $output .= '- ';
                    }
                    $output .= trim($node->nodeValue) . "\n";
                }
                $this->moviealternateversions[] = trim($output);
            }
        }
        return $this->moviealternateversions;
    }

    protected function getPage($page = null)
    {
        if (!empty($this->page[$page])) {
            return $this->page[$page];
        }

        $this->page[$page] = parent::getPage($page);

        return $this->page[$page];
    }

    protected function jsonLD()
    {
        if ($this->jsonLD) {
            return $this->jsonLD;
        }
        $page = $this->getPage("Title");
        preg_match('#<script type="application/ld\+json">(.+?)</script>#ims', $page, $matches);
        $this->jsonLD = json_decode($matches[1]);
        return $this->jsonLD;
    }
}

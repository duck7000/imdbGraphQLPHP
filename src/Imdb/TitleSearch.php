<?php

namespace Imdb;

class TitleSearch extends MdbBase
{

    public const MOVIE = 'Movie';
    public const TV_SERIES = 'TV Series';
    public const TV_EPISODE = 'TV Episode';
    public const TV_MINI_SERIES = 'TV Mini Series';
    public const TV_MOVIE = 'TV Movie';
    public const TV_SPECIAL = 'TV Special';
    public const TV_SHORT = 'TV Short';
    public const GAME = 'Video Game';
    public const VIDEO = 'Video';
    public const SHORT = 'Short';

    /**
     * Search IMDb for titles matching $searchTerms
     * @param string $searchTerms
     * @return Title[] array of Titles
     */
    public function search($searchTerms)
    {
        $results = array();
        $page = $this->getPage($searchTerms);
        if ($page != "") {
            $object = json_decode($page);
            foreach ($object->d as $match) {
                $imdbid = '';
                $title = '';
                $year = '';
                $movietype = '';
                if (isset($match->qid) && !empty($match->qid)) {
                    $movietype = $this->parseTitleType($match->qid);
                }
                if (isset($match->id) && !empty($match->id)) {
                    $imdbid = preg_replace('/[^0-9]+/', '', $match->id);
                }
                if (isset($match->l) && !empty($match->l)) {
                    $title = $match->l;
                }
                if (isset($match->yr) && !empty($match->yr)) {
                    $year = $match->yr;
                } elseif (isset($match->y) && !empty($match->y)) {
                    $year = $match->y;
                }
                
                $results[] = array(
                        'imdbid' => $imdbid,
                        'title' => $title,
                        'year' => $year,
                        'movietype' => $movietype
                    );
            }
            return $results;
        }
    }

    protected function parseTitleType($string)
    {
        $string = strtoupper($string);

        if (strpos($string, 'TVSERIES') !== false) {
            return self::TV_SERIES;
        } elseif (strpos($string, 'TVEPISODE') !== false) {
            return self::TV_EPISODE;
        } elseif (strpos($string, 'VIDEOGAME') !== false) {
            return self::GAME;
        } elseif (strpos($string, 'VIDEO') !== false) {
            return self::VIDEO;
        } elseif (strpos($string, 'SHORT') !== false) {
            return self::SHORT;
        } elseif (strpos($string, 'TVMINISERIES') !== false) {
            return self::TV_MINI_SERIES;
        } elseif (strpos($string, 'TVMOVIE') !== false) {
            return self::TV_MOVIE;
        } elseif (strpos($string, 'TVSPECIAL') !== false) {
            return self::TV_SPECIAL;
        } elseif (strpos($string, 'TVSHORT') !== false) {
            return self::TV_SHORT;
        } else {
            return self::MOVIE;
        }
    }

    protected function buildUrl($searchTerms = null)
    {
        $first = substr($searchTerms, 0, 1);
        return "https://v3.sg.media-imdb.com/suggestion/" . $first . "/" . urlencode($searchTerms) . ".json";
        //return "https://" . $this->imdbsite . "/find?s=tt&q=" . urlencode($searchTerms);
    }
}
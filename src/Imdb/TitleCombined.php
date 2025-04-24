<?php
#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written by Ed                                                             #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * A title on IMDb
 * @author Ed
 * @copyright (c) 2025 Ed
 */
class TitleCombined extends MdbBase
{

    protected $imageFunctions;
    protected $newImageWidth;
    protected $newImageHeight;
    protected $main = array();

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(string $id, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
        $this->imageFunctions = new Image();
        $this->newImageWidth = $this->config->photoThumbnailWidth;
        $this->newImageHeight = $this->config->photoThumbnailHeight;
    }

    /**
     * This method will only get main values of a imdb title (inside the black top part of the imdb page)
     * @return Array
        * (
            * [title] => A Clockwork Orange
            * [originalTitle] => A Clockwork Orange
            * [imdbid] => 0066921
            * [reDirectId] => (redirected ID or false)
            * [movieType] => Movie
            * [year] => 1971
            * [endYear] => 
            * [imgThumb] => https://m.media-amazon.com/images/M/MV5BMTY3MjM1Mzc4N15BMl5BanBnXkFtZTgwODM0NzAxMDE@._V1_QL75_SX190_CR0,0,190,281_.jpg (190x281 pixels)
            * [imgFull] => https://m.media-amazon.com/images/M/MV5BMTY3MjM1Mzc4N15BMl5BanBnXkFtZTgwODM0NzAxMDE@._V1_QL100_SX1000_.jpg (max 1000 pixels)
            * [runtime] => 136
            * [rating] => 8.2
            * [genre] => Array
                * (
                    * [0] => Array
                        * (
                            * [mainGenre] => Crime
                            * [subGenre] => Array
                                * (
                                * )
                        * )
                    * [1] => Array
                        * (
                            * [mainGenre] => Sci-Fi
                            * [subGenre] => Array
                                * (
                                    * [0] => dystopian sci fi
                                * )
                        * )
                * )
            * [plotoutline] => Alex DeLarge and his droogs barbarize a decaying near-future.
            * [credits] => Array
                * (
                    * [Director] => Array
                        * (
                            * [0] => Array
                                * (
                                    * [name] => Stanley Kubrick
                                    * [imdbid] => 0000040
                                * )
                        * )
                    * [Writer] => Array
                        * (
                            * [0] => Array
                                * (
                                    * [name] => Stanley Kubrick
                                    * [imdbid] => 0000040
                                * )
                            * [1] => Array
                                * (
                                    * [name] => Anthony Burgess
                                    * [imdbid] => 0121256
                                * )
                        * )
                    * [Star] => Array
                        * (
                            * [0] => Array
                                * (
                                    * [name] => Malcolm McDowell
                                    * [imdbid] => 0000532
                                * )
                            * [1] => Array
                                * (
                                    * [name] => Patrick Magee
                                    * [imdbid] => 0535861
                                * )
                            * [2] => Array
                                * (
                                    * [name] => Michael Bates
                                    * [imdbid] => 0060988
                                * )
                        * )
                * )
        * )
     */
    public function main()
    {
        $query = <<<EOF
query TitleCombinedMain(\$id: ID!) {
  title(id: \$id) {
    titleText {
      text
    }
    originalTitleText {
      text
    }
    titleType {
      text
    }
    releaseYear {
      year
      endYear
    }
    primaryImage {
      url
      width
      height
    }
    runtime {
      seconds
    }
    ratingsSummary {
      aggregateRating
    }
    titleGenres {
      genres {
        genre {
          text
        }
        subGenres {
          keyword {
            text {
              text
            }
          }
        }
      }
    }
    plot {
      plotText {
        plainText
      }
    }
    principalCredits {
      credits(limit: 3) {
        name {
          nameText {
            text
          }
          id
        }
        category {
          text
        }
      }
    }
    meta {
      canonicalId
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "TitleCombinedMain", ["id" => "tt$this->imdbID"]);
        if (!isset($data->title)) {
            return $this->main;
        }
        $this->main = array(
            'title' => isset($data->title->titleText->text) ?
                             trim(str_replace('"', ':', trim($data->title->titleText->text, '"'))) : null,
            'originalTitle' => isset($data->title->originalTitleText->text) ?
                                     trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"'))) : null,
            'imdbid' => $this->imdbID,
            'reDirectId' => isset($data->title->meta->canonicalId) ?
                                  $this->checkRedirect($data->title->meta->canonicalId) : false,
            'movieType' => isset($data->title->titleType->text) ?
                                 $data->title->titleType->text : null,
            'year' => isset($data->title->releaseYear->year) ?
                            $data->title->releaseYear->year : null,
            'endYear' => isset($data->title->releaseYear->endYear) ?
                               $data->title->releaseYear->endYear : null,
            'imgThumb' => isset($data->title->primaryImage) ?
                                $this->populatePoster($data->title->primaryImage, true) : null,
            'imgFull' => isset($data->title->primaryImage) ?
                               $this->populatePoster($data->title->primaryImage, false) : null,
            'runtime' => isset($data->title->runtime->seconds) ?
                               $data->title->runtime->seconds / 60 : 0,
            'rating' => isset($data->title->ratingsSummary->aggregateRating) ?
                              $data->title->ratingsSummary->aggregateRating : 0,
            'genre' => isset($data->title->titleGenres->genres) ?
                             $this->genre($data->title->titleGenres->genres) : null,
            'plotoutline' => isset($data->title->plot->plotText->plainText) ?
                                   $data->title->plot->plotText->plainText : null,
            'credits' => isset($data->title->principalCredits) ?
                               $this->principalCredits($data->title->principalCredits) : null
        );
        return $this->main;
    }


    #========================================================[ Helper functions ]===

    #--------------------------------------------------------------[ Photo/Poster ]---
    /**
     * Setup cover photo (thumbnail and big variant)
     * @param object $primaryImage primary image object found in main()
     * @see IMDB page / (TitlePage)
     */
    private function populatePoster($primaryImage, $thumb)
    {
        if (isset($primaryImage->url)) {
            $img = str_replace('.jpg', '', $primaryImage->url);
            if ($thumb === true) {
                $fullImageWidth = $primaryImage->width;
                $fullImageHeight = $primaryImage->height;
                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                return $img . $parameter;
            } else {
                return $img . 'QL100_SX1000_.jpg';
            }
        }
        return null;
    }

    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @param array $genreArray found genres array from main()
     * @return array genres (array[0..n] of mainGenre| string, subGenre| array())
     * @see IMDB page / (TitlePage)
     */
    private function genre($genreArray)
    {
        if (is_array($genreArray) && count($genreArray) > 0) {
            foreach ($genreArray as $edge) {
                $subGenres = array();
                if (isset($edge->subGenres) &&
                    is_array($edge->subGenres) &&
                    count($edge->subGenres) > 0
                    )
                {
                    foreach ($edge->subGenres as $subGenre) {
                        if (!empty($subGenre->keyword->text->text)) {
                            $subGenres[] = $subGenre->keyword->text->text;
                        }
                    }
                }
                $mainGenres[] = array(
                    'mainGenre' => isset($edge->genre->text) ?
                                         $edge->genre->text : null,
                    'subGenre' => $subGenres
                );
            }
            return $mainGenres;
        }
        return array();
    }

    #----------------------------------------------------------------[ PrincipalCredits ]---
    /*
    * Get the PrincipalCredits for this title
    * @param array $principalCredits principal credits array from main()
    * @return array creditsPrincipal[category][Director, Writer, Creator, Stars] (array[0..n] of array[name,imdbid])
    */
    private function principalCredits($principalCredits)
    {
        $creditsPrincipal = array();
        if (is_array($principalCredits) && count($principalCredits) > 0) {
            foreach ($principalCredits as $value){
                $category = 'Unknown';
                $credits = array();
                if (!empty($value->credits[0]->category->text)) {
                    $category = $value->credits[0]->category->text;
                    if ($category == "Actor" || $category == "Actress") {
                        $category = "Star";
                    }
                }
                if (isset($value->credits) &&
                    is_array($value->credits) &&
                    count($value->credits) > 0
                    )
                {
                    foreach ($value->credits as $credit) {
                        $credits[] = array(
                            'name' => isset($credit->name->nameText->text) ?
                                            $credit->name->nameText->text : null,
                            'imdbid' => isset($credit->name->id) ?
                                            str_replace('nm', '', $credit->name->id) : null
                        );
                    }
                } elseif ($category == 'Unknown') {
                    continue;
                }
                $creditsPrincipal[$category] = $credits;
            }
        }
        return $creditsPrincipal;
    }

    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not
     * Sometimes it happens that imdb redirects an existing id to a new id
     * @param string $titleImdbId the returned imdbid from Graphql call
     * @return string $titleImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    private function checkRedirect($titleImdbId)
    {
        $titleImdbId = str_replace('tt', '', $titleImdbId);
        if ($titleImdbId  != $this->imdbID) {
            // todo write to log?
            return $titleImdbId;
        } else {
            return false;
        }
    }

}

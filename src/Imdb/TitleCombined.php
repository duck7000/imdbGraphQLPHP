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

/**
 * A title on IMDb
 * @author Ed
 * @copyright (c) 2024 Ed
 */
class TitleCombined extends MdbBase
{

    protected $main = array();
    protected $mainCreditsPrincipal = array();
    protected $mainPoster = "";
    protected $mainPosterThumb = "";
    protected $mainPlotoutline = "";
    protected $mainMovietype = "";
    protected $mainTitle = "";
    protected $mainOriginalTitle = "";
    protected $mainYear = -1;
    protected $mainEndYear = -1;
    protected $mainRating = 0;
    protected $mainGenres = array();
    protected $mainRuntime = 0;
    protected $mainCanonicalId = false;

    /**
     * @param string $id IMDb ID. e.g. 285331 for https://www.imdb.com/title/tt0285331/
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct($id, Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
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
      credits {
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

        $this->mainTitle = trim(str_replace('"', ':', trim($data->title->titleText->text, '"')));
        $this->mainOriginalTitle  = trim(str_replace('"', ':', trim($data->title->originalTitleText->text, '"')));
        $this->mainMovietype = isset($data->title->titleType->text) ? $data->title->titleType->text : '';
        $this->mainYear = isset($data->title->releaseYear->year) ? $data->title->releaseYear->year : '';
        $this->mainEndYear = isset($data->title->releaseYear->endYear) ? $data->title->releaseYear->endYear : null;
        if ($this->mainYear == "????") {
            $this->mainYear = "";
        }
        $this->mainRuntime = isset($data->title->runtime->seconds) ? $data->title->runtime->seconds / 60 : 0;
        $this->mainRating = isset($data->title->ratingsSummary->aggregateRating) ? $data->title->ratingsSummary->aggregateRating : 0;
        $this->mainPlotoutline = isset($data->title->plot->plotText->plainText) ? $data->title->plot->plotText->plainText : "";
        $this->mainCanonicalId = $this->checkRedirect($data);
        
        // Image
        $this->populatePoster($data);

        // Genres
        $this->genre($data);

        // Credits
        $this->principalCredits($data);

        $this->main = array(
            'title' => $this->mainTitle,
            'originalTitle' => $this->mainOriginalTitle,
            'imdbid' => $this->imdbID,
            'reDirectId' => $this->mainCanonicalId,
            'movieType' => $this->mainMovietype,
            'year' => $this->mainYear,
            'endYear' => $this->mainEndYear,
            'imgThumb' => $this->mainPosterThumb,
            'imgFull' => $this->mainPoster,
            'runtime' => $this->mainRuntime,
            'rating' => $this->mainRating,
            'genre' => $this->mainGenres,
            'plotoutline' => $this->mainPlotoutline,
            'credits' => $this->mainCreditsPrincipal
        );
        return $this->main;
    }


    #========================================================[ Helper functions ]===
    #===============================================================================

    #========================================================[ photo/poster ]===
    /**
     * Setup cover photo (thumbnail and big variant)
     * @see IMDB page / (TitlePage)
     */
    private function populatePoster($data)
    {
        if (isset($data->title->primaryImage->url) && $data->title->primaryImage->url != null) {
            $fullImageWidth = $data->title->primaryImage->width;
            $fullImageHeight = $data->title->primaryImage->height;
            $newImageWidth = 190;
            $newImageHeight = 281;
            $img = str_replace('.jpg', '', $data->title->primaryImage->url);
            $parameter = $this->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            
            // thumb image
            $this->mainPosterThumb = $img . $parameter;
            
            // full image
            $this->mainPoster = $img . 'QL100_SX1000_.jpg';
        }
    }

    /**
     * Calculate The total result parameter and determine if SX or SY is used
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return string example 'QL100_SX190_CR0,15,190,281_.jpg'
     * QL100 = Quality Level, 100 the highest, 0 the lowest quality
     * SX190 = S (scale) X190 desired width
     * CR = Crop (crop left and right, crop top and bottom, New width, New Height)
     * @see IMDB page / (TitlePage)
     */
    private function resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        // original source aspect ratio
        $ratio_orig = $fullImageWidth / $fullImageHeight;

        // new aspect ratio
        $ratio_new = $newImageWidth / $newImageHeight;

        // check if the image must be treated as SX or SY
        if ($ratio_new < $ratio_orig) {
            $cropParameter = $this->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_SY' . $newImageHeight . '_CR' . $cropParameter . ',0,' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        } else {
            $cropParameter = $this->thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_SX' . $newImageWidth . '_CR0,' . $cropParameter . ',' . $newImageWidth .',' . $newImageHeight . '_.jpg';
        }
    }

    /**
     * Calculate if cropValue has to be round to previous or next even integer
     * @parameter $totalPixelCropSize how much pixels in total need to be cropped
     */
    private function roundInteger($totalPixelCropSize)
    {
        if ((($totalPixelCropSize - floor($totalPixelCropSize)) < 0.5)) {
            // Previous even integer
            $num = 2 * round($totalPixelCropSize / 2.0);
        } else {
            // Next even integer
            $num = ceil($totalPixelCropSize);
            $num += $num % 2;
        }
        return $num;
    }

    /**
     * Calculate HORIZONTAL (left and right) crop value for primary, cast, episode, recommendations and mainphoto images
     * Output is for portrait images!
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    private function thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $scaledWidth = $fullImageWidth / $newScalefactor;
        $totalPixelCropSize = $scaledWidth - $newImageWidth;
        $cropValue = max($this->roundInteger($totalPixelCropSize)/2, 0);
        return $cropValue;
    }

    /**
     * Calculate VERTICAL (Top and bottom)crop value for primary, cast, episode and recommendations images
     * Output is for landscape images!
     * @parameter $fullImageWidth the width in pixels of the large original image
     * @parameter $fullImageHeight the height in pixels of the large original image
     * @parameter $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @parameter $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @see IMDB page / (TitlePage)
     */
    private function thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageWidth / $newImageWidth;
        $scaledHeight = $fullImageHeight / $newScalefactor;
        $totalPixelCropSize = $scaledHeight - $newImageHeight;
        $cropValue = max($this->roundInteger($totalPixelCropSize)/2, 0);
        return $cropValue;
    }
    
    #--------------------------------------------------------------[ Genre(s) ]---
    /** Get all genres the movie is registered for
     * @return array genres (array[0..n] of mainGenre| string, subGenre| array())
     * @see IMDB page / (TitlePage)
     */
    private function genre($data)
    {
        if (empty($this->mainGenres)) {
            if (isset($data->title->titleGenres->genres) && !empty($data->title->titleGenres->genres)) {
                foreach ($data->title->titleGenres->genres as $edge) {
                    $subGenres = array();
                    if (isset($edge->subGenres) && !empty($edge->subGenres)) {
                        foreach ($edge->subGenres as $subGenre) {
                            $subGenres[] = $subGenre->keyword->text->text;
                        }
                    }
                    $this->mainGenres[] = array(
                        'mainGenre' => $edge->genre->text,
                        'subGenre' => $subGenres
                    );
                }
            }
        }
    }
    
    #=====================================================[ /fullcredits page ]===
    #----------------------------------------------------------------[ PrincipalCredits ]---
    /*
    * Get the PrincipalCredits for this title
    * @return array creditsPrincipal[category][Director, Writer, Creator, Stars] (array[0..n] of array[name,imdbid])
    * Not all categories are always available, TV series has Creator instead of writer
    */
    private function principalCredits($data)
    {
        if (empty($this->mainCreditsPrincipal)) {
            foreach ($data->title->principalCredits as $value){
                $cat = $value->credits[0]->category->text;
                if ($cat == "Actor" || $cat == "Actress") {
                    $category = "Star";
                } else {
                    $category = $cat;
                }
                $temp = array();
                foreach ($value->credits as $key => $credit) {
                    $temp[] = array(
                        'name' => isset($credit->name->nameText->text) ? $credit->name->nameText->text : '',
                        'imdbid' => isset($credit->name->id) ? str_replace('nm', '', $credit->name->id) : ''
                    );
                    if ($key == 2) {
                        break;
                    }
                }
                $this->mainCreditsPrincipal[$category] = $temp;
            }
        }
    }
    
    #----------------------------------------------------------[ imdbID redirect ]---
    /**
     * Check if imdbid is redirected to another id or not.
     * It sometimes happens that imdb redirects an existing id to a new id.
     * If user uses search class this check isn't nessecary as the returned results already contain a possible new imdbid
     * @var $this->imdbID The imdbid used to call this class
     * @var $titleImdbId the returned imdbid from Graphql call (in some cases this can be different)
     * @return $titleImdbId (the new redirected imdbId) or false (no redirect)
     * @see IMDB page / (TitlePage)
     */
    public function checkRedirect($data)
    {
        $titleImdbId = str_replace('tt', '', $data->title->meta->canonicalId);
        if ($titleImdbId  != $this->imdbID) {
            // todo write to log?
            return $titleImdbId;
        }
    }

}

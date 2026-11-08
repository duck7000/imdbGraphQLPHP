<?php

#############################################################################
# imdbGraphQLPHP                         https://www.imdb.com/trailers/     #
# written by Ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Imdb\Image;

/**
 * Obtains news about titles, persons tv news
 * @Note thumbnail width and height are set in config, one setting for all methods!
 * @author Ed (github user: duck7000)
 */
class News extends MdbBase
{

    protected $imageFunctions;
    protected $newImageWidth;
    protected $newImageHeight;

    /**
     * @param string Config $config OPTIONAL override default config
     * @param string LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param string CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->imageFunctions = new Image();
        $this->newImageWidth = 500;
        $this->newImageHeight = 281;
    }

    /**
     * Get the latest news list for Movie, tv, top, celebrity or indie
     * @note Thumbnail size = fixed 500x281
     * @note max 250 items are returned, this covers about a year
     * @param string $listType determines which list to return
     * possible values for $listType:
     *      CELEBRITY
     *      INDIE
     *      MOVIE
     *      TOP
     *      TV
     * @return array
     *      [0]  Array()
     *          [id]              string (without ni)
     *          [title]           string news item title
     *          [author]          string
     *          [date]            string iso date string
     *          [extUrl]          string
     *          [exturlLabel]     string label used for extUrl
     *          [textHtml]        string
     *          [textText]        string
     *          [thumbnailUrl]    string
     */
    public function newsList($listType = "MOVIE")
    {
        $newsListItems = array();
        $query = <<<EOF
query News{
  news(first: 250, category: $listType) {
    edges {
      node {
        articleTitle {
          plainText
        }
        byline
        date
        externalUrl
        id
        image {
          url
          width
          height
        }
        source {
          homepage {
            label
          }
        }
        text {
          plainText
          plaidHtml
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "News");
        if (!isset($data->news)) {
            return $newsListItems;
        }
        if (isset($data->news->edges) &&
            is_array($data->news->edges) &&
            count($data->news->edges) > 0
           )
        {
            foreach ($data->news->edges as $edge) {
                $thumbUrl = null;
                if (!empty($edge->node->image->url)) {
                    $fullImageWidth = $edge->node->image->width;
                    $fullImageHeight = $edge->node->image->height;
                    $img = str_replace('.jpg', '', $edge->node->image->url);
                    $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $this->newImageWidth, $this->newImageHeight);
                    $thumbUrl = $img . $parameter;
                }
                $newsListItems[] = array(
                    'id' => isset($edge->node->id) ?
                                  str_replace('ni', '', $edge->node->id) : null,
                    'title' => isset($edge->node->articleTitle->plainText) ?
                                     $edge->node->articleTitle->plainText : null,
                    'author' => isset($edge->node->byline) ?
                                      $edge->node->byline : null,
                    'date' => isset($edge->node->date) ?
                                    $edge->node->date : null,
                    'extUrl' => isset($edge->node->externalUrl) ?
                                      $edge->node->externalUrl : null,
                    'exturlLabel' => isset($edge->node->source->homepage->label) ?
                                           $edge->node->source->homepage->label : null,
                    'textHtml' => isset($edge->node->text->plaidHtml) ?
                                        $edge->node->text->plaidHtml : null,
                    'textText' => isset($edge->node->text->plainText) ?
                                        $edge->node->text->plainText : null,
                    'thumbnailUrl' => $thumbUrl
                );
            }
        }
        return $newsListItems;
    }

}

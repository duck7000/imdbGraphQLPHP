<?php
#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written & maintained by ed (github user: duck7000)                        #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Imdb\Image;

/**
 * User related stuff on IMDb
 * @author Ed
 */
class User extends MdbBase
{

    protected $imageFunctions;
    protected $watchList = array();
    protected $favoriteActorsList = array();
    protected $notInterestedList = array();
    protected $seenList = array();
    protected $userListTitles = array();
    protected $userListPeople = array();

    /**
     * @param string $id IMDb USER ID. e.g. ur285331
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(string $id, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
        $this->imageFunctions = new Image();
    }

    #-------------------------------------------------------------[ User ]---

    /**
     * Get public predefind watch_list of a User Id
     * @return array()
     */
    public function watch()
    {
        $this->watchList = $this->ListHelperPredefind('WATCH_LIST');
        return $this->watchList;
    }

    /**
     * Get public predefind favorite_actors list of a User Id
     * @return array()
     */
    public function favoriteActors()
    {
        $this->favoriteActorsList = $this->ListHelperPredefind('FAVORITE_ACTORS');
        return $this->favoriteActorsList;
    }

    /**
     * Get public predefind not_interested list of a User Id
     * @return array()
     */
    public function notInterested()
    {
        $this->notInterestedList = $this->ListHelperPredefind('NOT_INTERESTED');
        return $this->notInterestedList;
    }

    /**
     * Get public predefind seen list of a User Id
     * @return array()
     */
    public function seen()
    {
        $this->seenList = $this->ListHelperPredefind('SEEN');
        return $this->seenList;
    }

    /**
     * Get public user defined Titles list of a User Id
     * @return array()
     */
    public function userTitles()
    {
        $this->userListTitles = $this->ListHelperUser('TITLES');
        return $this->userListTitles;
    }

    /**
     * Get public user defined People list of a User Id
     * @return array()
     */
    public function userPeople()
    {
        $this->userListPeople = $this->ListHelperUser('PEOPLE');
        return $this->userListPeople;
    }

    #========================================================[ Helper functions ]===

    /**
     * Get public predefind lists like watchList of a User Id
     * @param string $listClass ENUM list class: SEEN, NOT_INTERESTED, FAVORITE_THEATRES, FAVORITE_ACTORS, CHECK_INS, WATCH_LIST
     * @return array()
     */
    protected function ListHelperPredefind($listClass)
    {
        $results = array();
        $query = <<<EOF
query WatchListPage {
  predefinedList(classType: $listClass userId: "ur$this->imdbID"){
    id
    name {
      originalText
    }
    createdDate
    lastModifiedDate
    listType {
     id
    }
    author {
      userId
      nickName
    }
    items(first:1000) {
      edges {
        node {
          item {
            ... on Title {
              id
              titleText {
                text
              }
              titleType {
                text
              }
              primaryImage {
                url
                width
                height
              }
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "WatchListPage", ["id" => "ur$this->imdbID"]);
        $items = array();
        if (isset($data->predefinedList->items->edges) &&
            is_array($data->predefinedList->items->edges)
           )
        {
            foreach ($data->predefinedList->items->edges as $item) {
                $thumb = '';
                if (!empty($item->node->item->primaryImage->url)) {
                    $img = str_replace('.jpg', '', $item->node->item->primaryImage->url);
                    if (!empty($item->node->item->primaryImage->width) &&
                        !empty($item->node->item->primaryImage->height)
                       )
                    {
                        $fullImageWidth = $item->node->item->primaryImage->width;
                        $fullImageHeight = $item->node->item->primaryImage->height;
                        $newImageWidth = $this->config->photoThumbnailWidth;
                        $newImageHeight = $this->config->photoThumbnailHeight;
                        $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                        $thumb = $img . $parameter;
                    }
                }
                $items[] = array(
                    'id' => isset($item->node->item->id) ? $item->node->item->id : null,
                    'title' => isset($item->node->item->titleText->text) ?
                                     $item->node->item->titleText->text : null,
                    'titleType' => isset($item->node->item->titleType->text) ?
                                         $item->node->item->titleType->text : null,
                    'thumb' => $thumb
                );
            }
            $results = array(
                'id' => isset($data->predefinedList->id) ? $data->predefinedList->id : null,
                'name' => isset($data->predefinedList->name->originalText) ?
                                    $data->predefinedList->name->originalText : null,
                'createdDate' => isset($data->predefinedList->createdDate) ?
                                     $data->predefinedList->createdDate : null,
                'lastModifiedDate' => isset($data->predefinedList->lastModifiedDate) ?
                                     $data->predefinedList->lastModifiedDate : null,
                'listTypeId' => isset($data->predefinedList->listType->id) ?
                                      $data->predefinedList->listType->id : null,
                'authorId' => isset($data->predefinedList->author->userId) ?
                                    $data->predefinedList->author->userId : null,
                'authorNickName' => isset($data->predefinedList->author->nickName) ?
                                    $data->predefinedList->author->nickName : null,
                'items' => $items
            );
        }
        return $results;
    }

    /**
     * Get public user made lists of a User Id
     * @param string $listElementType ENUM list class: TITLES, PEOPLE, VIDEOS, IMAGES, GALLERIES
     * @return array()
     */
    protected function ListHelperUser($listElementType)
    {
        $userResults = array();
        $query = <<<EOF
query UserList {
  lists(
    first: 1000
    listOwnerUserId: "ur$this->imdbID"
    filter:{
      classTypes: LIST
      listElementType: $listElementType
    } 
  ) {
    edges{
      node{
        id
        listType {
          id
        }
        author {
          userId
          nickName
        }
        createdDate
        lastModifiedDate
        name {
          originalText
        }
        items (first:1000) {
          edges {
            node {
              item {
                ... on Title {
                  id
                  titleText {
                    text
                  }
                  titleType {
                    text
                  }
                  primaryImage {
                    url
                    width
                    height
                  }
                }
                ... on Name {
                  id
                  nameText {
                    text
                  }
                  primaryImage {
                    url
                    width
                    height
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
EOF;
        $data = $this->graphql->query($query, "UserList", ["id" => "ur$this->imdbID"]);
        if (isset($data->lists->edges) &&
            is_array($data->lists->edges) &&
            count($data->lists->edges) > 0
           )
        {
            foreach ($data->lists->edges as $item) {
                $userItems = array();
                if (isset($item->node->items->edges) &&
                    is_array($item->node->items->edges) &&
                    count($item->node->items->edges) > 0
                   )
                {
                    foreach ($item->node->items->edges as $listItems) {
                        $thumb = '';
                        if (!empty($listItems->node->item->primaryImage->url)) {
                            $img = str_replace('.jpg', '', $listItems->node->item->primaryImage->url);
                            if (!empty($listItems->node->item->primaryImage->width) &&
                                !empty($listItems->node->item->primaryImage->height)
                               )
                            {
                                $fullImageWidth = $listItems->node->item->primaryImage->width;
                                $fullImageHeight = $listItems->node->item->primaryImage->height;
                                $newImageWidth = $this->config->photoThumbnailWidth;
                                $newImageHeight = $this->config->photoThumbnailHeight;
                                $parameter = $this->imageFunctions->resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
                                $thumb = $img . $parameter;
                            }
                        }
                        if ($listElementType === 'PEOPLE') {
                            $userItems['names'][] = array(
                                'id' => isset($listItems->node->item->id) ? $listItems->node->item->id : null,
                                'name' => isset($listItems->node->item->nameText->text) ?
                                                $listItems->node->item->nameText->text : null,
                                'thumb' => $thumb
                            );
                        } elseif ($listElementType === 'TITLES') {
                            $userItems['titles'][] = array(
                                'id' => isset($listItems->node->item->id) ? $listItems->node->item->id : null,
                                'title' => isset($listItems->node->item->titleText->text) ?
                                                 $listItems->node->item->titleText->text : null,
                                'titleType' => isset($listItems->node->item->titleType->text) ?
                                                     $listItems->node->item->titleType->text : null,
                                'thumb' => $thumb
                            );
                        }
                    }
                }
                $userResults[] = array(
                    'id' => isset($item->node->id) ? $item->node->id : null,
                    'name' => isset($item->node->name->originalText) ?
                                    $item->node->name->originalText : null,
                    'createdDate' => isset($item->node->createdDate) ?
                                           $item->node->createdDate : null,
                    'lastModifiedDate' => isset($item->node->lastModifiedDate) ?
                                                $item->node->lastModifiedDate : null,
                    'listTypeId' => isset($item->node->listType->id) ?
                                          $item->node->listType->id : null,
                    'authorId' => isset($item->node->author->userId) ?
                                        $item->node->author->userId : null,
                    'authorNickName' => isset($item->node->author->nickName) ?
                                              $item->node->author->nickName : null,
                    'items' => $userItems
                );
            }
        }
        return $userResults;
    }
}

<?php

namespace dukt\videos\dailymotion\gateways;

use Craft;
use dukt\videos\base\Gateway;
use dukt\videos\models\Collection;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use GuzzleHttp\Client;

/**
 * Dailymotion gateway
 */
class Dailymotion extends Gateway
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the icon URL.
     *
     * @return string|false|null
     */
    public function getIconUrl()
    {
        $iconAlias = '@dukt/videos/dailymotion/icon.svg';

        if (file_exists(Craft::getAlias($iconAlias))) {
            return Craft::$app->assetManager->getPublishedUrl($iconAlias, true);
        }
    }

    /**
     * @inheritDoc GatewayInterface::getName()
     *
     * @return string
     */
    public function getName()
    {
        return "Dailymotion";
    }

    /**
     * @inheritDoc GatewayInterface::getOauthProviderHandle()
     *
     * @return string
     */
    public function getOauthProviderHandle()
    {
        return 'dailymotion';
    }

    /**
     * Returns the OAuth provider’s name.
     *
     * @return string
     */
    public function getOauthProviderName()
    {
        return 'Dailymotion';
    }

    /**
     * Returns the OAuth provider’s API console URL.
     *
     * @return string
     */
    public function getOauthProviderApiConsoleUrl()
    {
        return 'http://www.dailymotion.com/settings/developer';
    }

    /**
     * Creates the OAuth provider.
     *
     * @param $options
     *
     * @return \dukt\videos\dailymotion\oauth2\client\provider\Dailymotion
     */
    public function createOauthProvider($options)
    {
        return new \dukt\videos\dailymotion\oauth2\client\provider\Dailymotion($options);
    }

    /**
     * @inheritDoc GatewayInterface::getExplorerSections()
     *
     * @return array
     */
    public function getExplorerSections()
    {
        $sections = [];


        // Library

        $sections[] = new Section([
            'name' => "Library",
            'collections' => [
                new Collection([
                    'name' => "Uploads",
                    'method' => 'uploads',
                ]),
                new Collection([
                    'name' => "Likes",
                    'method' => 'likes',
                ]),
                new Collection([
                    'name' => "History",
                    'method' => 'history',
                ]),
            ]
        ]);


        // Playlists

        $playlists = $this->getCollectionsPlaylists();

        if (is_array($playlists)) {
            $collections = [];

            foreach ($playlists as $playlist) {
                $collections[] = new Collection([
                    'name' => $playlist['title'],
                    'method' => 'playlist',
                    'options' => ['id' => $playlist['id']]
                ]);
            }

            if (count($collections) > 0) {
                $sections[] = new Section([
                    'name' => "Playlists",
                    'collections' => $collections,
                ]);
            }
        }

        return $sections;
    }

    /**
     * @inheritDoc GatewayInterface::getVideoById()
     *
     * @param $id
     *
     * @return Video
     * @throws \Exception
     */
    public function getVideoById($id)
    {
        $query = [
            'fields' => $this->getFields()
        ];

        $response = $this->apiGet('video/'.$id, $query);

        $data = $response;

        if ($data) {
            return $this->parseVideo($data);
        }
    }

    /**
     * @inheritDoc GatewayInterface::getEmbedFormat()
     *
     * @return string
     */
    public function getEmbedFormat()
    {
        return "//www.dailymotion.com/embed/video/%s";
    }

    /**
     * @inheritDoc GatewayInterface::extractVideoIdFromUrl()
     *
     * @param $url
     *
     * @return bool|int
     */
    public function extractVideoIdFromUrl($url)
    {
        // check if url works with this service and extract video_id

        $videoId = false;

        $regexp = ['/^https?:\/\/(www\.)?dailymotion\.com\/video\/([a-z0-9]*)/', 2];

        if (preg_match($regexp[0], $url, $matches, PREG_OFFSET_CAPTURE) > 0) {

            // regexp match key
            $match_key = $regexp[1];


            // define video id
            $videoId = $matches[$match_key][0];


            // Fixes the youtube &feature_gdata bug
            if (strpos($videoId, "&")) {
                $videoId = substr($videoId, 0, strpos($videoId, "&"));
            }
        }

        // here we should have a valid video_id or false if service not matching
        return $videoId;
    }

    public function getSettingsTemplatePath()
    {
        return 'videos-dailymotion/_gateway';
    }

    // Protected
    // =========================================================================

    /**
     * Returns a list of videos in an playlist
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosPlaylist($params = [])
    {
        $playlistId = $params['id'];
        unset($params['id']);

        $params['fields'] = $this->getFields();

        // playlists/#playlist_id
        return $this->performVideosRequest('playlist/'.$playlistId.'/videos', $params);
    }

    /**
     * Returns a list of like videos
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosLikes($params = [])
    {
        $params['fields'] = $this->getFields();
        return $this->performVideosRequest('me/likes', $params);
    }

    /**
     * Returns a list of like videos
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosHistory($params = [])
    {
        $params['fields'] = $this->getFields();
        return $this->performVideosRequest('me/history', $params);
    }

    /**
     * Returns a list of videos from a search request
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosSearch($params = [])
    {
        $params['fields'] = $this->getFields();

        return $this->performVideosRequest('videos', $params);
    }

    /**
     * Returns a list of uploaded videos
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosUploads($params = [])
    {
        $params['fields'] = $this->getFields();
        return $this->performVideosRequest('me/videos', $params);
    }

    /**
     * Returns an authenticated Guzzle client
     *
     * @return Client
     */
    protected function createClient()
    {
        $options = [
            'base_uri' => $this->getApiUrl(),
            'headers' => [
                'Authorization' => 'Bearer '.$this->token->getToken()
            ],
        ];

        return new Client($options);
    }

    /**
     * @inheritDoc GatewayInterface::getOauthScope()
     *
     * @return array
     */
    public function getOauthScope()
    {
        return [
            'email',
        ];
    }

    // Private Methods
    // =========================================================================

    private function getFields()
    {
        return 'id,title,owner,owner.screenname,owner.url,created_time,duration,description,id,views_total,title,url,private,thumbnail_url';
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return 'https://api.dailymotion.com/';
    }

    /**
     * @return string
     */
    private function getApiVersion()
    {
        return '3.0';
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    private function getCollectionsPlaylists($params = [])
    {
        $query = $this->queryFromParams();
        $response = $this->apiGet('me/playlists', $query);

        return $this->parseCollections('playlist', $response['list']);
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseCollectionPlaylist($data)
    {
        $collection = [];
        $collection['id'] = $data['id'];
        $collection['title'] = $data['name'];

        return $collection;
    }

    /**
     * @param $type
     * @param $data
     *
     * @return array
     */
    private function parseCollections($type, $data)
    {
        $collections = [];

        foreach ($data as $channel) {
            $collection = $this->{'parseCollection'.ucwords($type)}($channel);

            array_push($collections, $collection);
        }

        return $collections;
    }

    /**
     * @param $data
     *
     * @return Video
     */
    private function parseVideo($data)
    {
        $video = new Video;
        $video->raw = $data;
        $video->authorName = $data['owner.screenname'];
        $video->authorUrl = $data['owner.url'];
        $video->date = $data['created_time'];
        $video->durationSeconds = $data['duration'];
        $video->description = $data['description'];
        $video->gatewayHandle = "dailymotion";
        $video->gatewayName = "DailyMotion";
        $video->id = $data['id'];
        $video->plays = (isset($data['views_total']) ? $data['views_total'] : 0);
        $video->title = $data['title'];
        $video->url = $data['url'];
        $video->private = $data['private'];
        $video->thumbnailSource = $data['thumbnail_url'];

        return $video;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseVideos($data)
    {
        $videos = [];

        if (!empty($data)) {
            foreach ($data as $videoData) {
                $video = $this->parseVideo($videoData);

                array_push($videos, $video);
            }
        }

        return $videos;
    }

    /**
     * @param      $uri
     * @param      $params
     * @param bool $requireAuthentication
     *
     * @return array
     * @throws \Exception
     */
    private function performVideosRequest($uri, $params, $requireAuthentication = true)
    {
        $query = $this->queryFromParams($params);

        $response = $this->apiGet($uri, $query);

        $videos = $this->parseVideos($response['list']);

        $more = false;
        $moreToken = null;

        if ($response['has_more']) {
            $more = true;
            $moreToken = $query['page'] + 1;
        }

        return [
            'videos' => $videos,
            'moreToken' => $moreToken,
            'more' => $more
        ];
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function queryFromParams($params = [])
    {
        $query = [];

        if (!empty($params['moreToken'])) {
            $query['page'] = $params['moreToken'];
            unset($params['moreToken']);
        } else {
            $query['page'] = 1;
        }

        if(!empty($params['fields'])) {
            $query['fields'] = $params['fields'];
        }

        if(!empty($params['q'])) {
            $query['search'] = $params['q'];
        }

        $query['limit'] = $this->getVideosPerPage();

/*        $query['full_response'] = 1;

        if (!empty($params['moreToken'])) {
            $query['page'] = $params['moreToken'];
            unset($params['moreToken']);
        } else {
            $query['page'] = 1;
        }

        // $params['moreToken'] = $query['page'] + 1;

        if (!empty($params['q'])) {
            $query['query'] = $params['q'];
            unset($params['q']);
        }

        $query['per_page'] = Videos::$plugin->getSettings()->videosPerPage;

        if (is_array($params)) {
            $query = array_merge($query, $params);
        }*/

        return $query;
    }
}

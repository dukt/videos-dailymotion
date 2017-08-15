<?php

namespace dukt\videos\dailymotion\gateways;

use dukt\videos\base\Gateway;
use dukt\videos\models\Collection;
use dukt\videos\models\Section;
use dukt\videos\models\Video;
use GuzzleHttp\Client;

/**
 * Dailymotion represents the Dailymotion gateway
 *
 * @author    Dukt <support@dukt.net>
 * @since     1.0
 */
class Dailymotion extends Gateway
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getIconAlias()
    {
        return '@dukt/videos/dailymotion/icon.svg';
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getName()
    {
        return "Dailymotion";
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
     * @param array $options
     *
     * @return \dukt\videos\dailymotion\oauth2\client\provider\Dailymotion
     */
    public function createOauthProvider(array $options)
    {
        return new \dukt\videos\dailymotion\oauth2\client\provider\Dailymotion($options);
    }

    /**
     * @inheritDoc
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
     * Returns a video from its ID.
     *
     * @param $id
     *
     * @return Video
     */
    public function getVideoById($id)
    {
        $client = $this->createClient();
        $response = $client->request('GET', 'video/'.$id, [
            'query' => [
                'fields' => $this->getVideoFields()
            ]
        ]);
        $body = $response->getBody();
        $data = json_decode($body, true);

        if ($data) {
            return $this->parseVideo($data);
        }
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEmbedFormat()
    {
        return "//www.dailymotion.com/embed/video/%s";
    }

    /**
     * @inheritDoc
     *
     * @param $url
     *
     * @return bool|string
     */
    public function extractVideoIdFromUrl($url)
    {
        $videoId = false;

        $regexp = ['/^https?:\/\/(www\.)?dailymotion\.com\/video\/([a-z0-9]*)/', 2];

        if (preg_match($regexp[0], $url, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $match_key = $regexp[1];
            $videoId = $matches[$match_key][0];
        }

        return $videoId;
    }

    // Protected
    // =========================================================================

    /**
     * Returns an authenticated Guzzle client.
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
     * Returns a list of videos in an playlist.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosPlaylist(array $params = [])
    {
        $playlistId = $params['id'];
        unset($params['id']);


        // playlists/#playlist_id
        return $this->performVideosRequest('playlist/'.$playlistId.'/videos', $params);
    }

    /**
     * Returns a list of like videos.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosLikes(array $params = [])
    {
        return $this->performVideosRequest('me/likes', $params);
    }

    /**
     * Returns a list of like videos.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosHistory(array $params = [])
    {
        return $this->performVideosRequest('me/history', $params);
    }

    /**
     * Returns a list of videos from a search request.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosSearch(array $params = [])
    {
        return $this->performVideosRequest('videos', $params);
    }

    /**
     * Returns a list of uploaded videos.
     *
     * @param array $params
     *
     * @return array
     */
    protected function getVideosUploads(array $params = [])
    {
        return $this->performVideosRequest('me/videos', $params);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns Dailymotion’s API URL.
     *
     * @return string
     */
    private function getApiUrl()
    {
        return 'https://api.dailymotion.com/';
    }

    /**
     * Returns playlists (collections) as an array.
     *
     * @return array
     */
    private function getCollectionsPlaylists()
    {
        $client = $this->createClient();
        $response = $client->request('GET', 'me/playlists');
        $body = $response->getBody();
        $data = json_decode($body, true);

        $collections = [];

        foreach ($data['list'] as $item) {
            $collections[] = [
                'id' => $item['id'],
                'title' => $item['name'],
            ];
        }

        return $collections;
    }

    /**
     * List of fields to be returned by the API when requesting videos.
     *
     * @return string
     */
    private function getVideoFields()
    {
        return 'id,title,owner,owner.screenname,owner.url,created_time,duration,description,id,views_total,title,url,private,thumbnail_url';
    }

    /**
     * Parses the API’s video data into a Video object.
     *
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
     * Request videos.
     *
     * @param      $uri
     * @param      $params
     *
     * @return array
     * @throws \Exception
     */
    private function performVideosRequest($uri, $params)
    {
        // Query

        $query = [];

        if (!empty($params['moreToken'])) {
            $query['page'] = $params['moreToken'];
            unset($params['moreToken']);
        } else {
            $query['page'] = 1;
        }

        if(!empty($params['q'])) {
            $query['search'] = $params['q'];
        }

        $query['fields'] = $this->getVideoFields();
        $query['limit'] = $this->getVideosPerPage();


        // Request

        $client = $this->createClient();
        $response = $client->request('GET', $uri, [
            'query' => $query
        ]);
        $body = $response->getBody();
        $data = json_decode($body, true);


        // Parse response

        $videos = [];

        if (!empty($data['list'])) {
            foreach  ($data['list'] as $item) {
                $videos[] = $this->parseVideo($item);
            }
        }


        // Return

        $more = false;
        $moreToken = null;

        if ($data['has_more']) {
            $more = true;
            $moreToken = $query['page'] + 1;
        }

        return [
            'videos' => $videos,
            'moreToken' => $moreToken,
            'more' => $more
        ];
    }
}

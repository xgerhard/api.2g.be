<?php

namespace App;

use Exception;
use GuzzleHttp\Client;

class TwitchAPI
{
    private $baseUrl = 'https://api.twitch.tv/helix/';
    private $v5 = 'application/vnd.twitchtv.v5+json';

    /**
     * Get users follows
     *
     * @param string $strFromId User Id, response is information about users who are being followed by this user
     * @param string $strToId User Id, response is information about users who are following this user
     * @param string $strAfter Cursor for forward pagination
     * @param int $iFirst Maximum number of objects to return
     *
     * @return json Twitch data response
     */
    public function getUsersFollows($strFromId = null, $strToId = null, $strAfter = null, $iFirst = null)
    {
        if(!$strFromId && !$strToId)
            throw new Exception('At minimum, from_id or to_id has to be provided');

        $a = [];
        if($strFromId)
            $a['from_id'] = $strFromId;
        if($strToId)
            $a['to_id'] = $strToId;
        if($strAfter)
            $a['after'] = $strAfter;
        if($iFirst)
            $a['first'] = $iFirst;

        return $this->request('users/follows?' . http_build_query($a));
    }

    /**
     * Search users
     *
     * @param array $aSearchUsers Array of usernames to search for
     *
     * @return json Twitch data response
     */
    public function searchUsers($aSearchUsers)
    {
        return $this->request(
            'https://api.twitch.tv/kraken/users?login='. urlencode(implode(',', $aSearchUsers)),
            true,
            ['Accept' => $this->v5]
        );
    }

    /**
     * Get chatters / viewerlist
     *
     * @param string $strChannel Channel name
     *
     * @return json Twitch data response
     */
    public function getChatters($strChannel)
    {
        return $this->request(
            'https://tmi.twitch.tv/group/user/'. strtolower($strChannel) .'/chatters',
            true
        );
    }

    /**
     * Fire requests with Guzzle
     *
     * @param string $strEndpoint Url or endpoint to retrieve
     * @param boolean $bFullUrl True for for url, false for helix endpoint
     * @param array $aHeaders Optional request headers
     *
     * @return json Twitch data response
     */
    private function request($strEndpoint, $bFullUrl = false, $aHeaders = [])
    {
        $aRequestHeaders = [
            'Client-ID' => ENV('TWITCH_CLIENT_ID')
        ];

        if(!empty($aHeaders))
            $aRequestHeaders = array_merge($aRequestHeaders, $aHeaders);

        $oGuzzle = new Client([
            //'http_errors' => false, 
            'verify' => false,
            'headers' => $aRequestHeaders
        ]);

        $res = $oGuzzle->request('GET', $bFullUrl ? $strEndpoint : $this->baseUrl . $strEndpoint);
        if($res->getStatusCode() == 200)
            return json_decode($res->getBody());
        else
            throw new Exception('Unexpected Twitch response');
    }
}
?>
<?php

namespace App;

use Exception;
use GuzzleHttp\Client;
use Log;
use App\OAuth\OAuthHandler;

class TwitchAPI
{
    private $baseUrl = 'https://api.twitch.tv/';

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

        return $this->request(
            'helix',
            'users/follows?' . http_build_query($a),
            false
        );
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
        $strPath = '';;
        foreach($aSearchUsers as $strSearhUser)
        {
            $strPath .= 'login='. urlencode($strSearhUser) .'&';
        }
        $strPath = substr($strPath, 0, -1);

        return $this->request(
            'helix',
            'users?'. $strPath,
            false
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
            'tmi',
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
    private function request($strVersion, $strEndpoint, $bFullUrl = false, $aHeaders = [])
    {
        $aRequestHeaders = [
            'Client-ID' => ENV('TWITCH_CLIENT_ID')
        ];

        switch($strVersion)
        {
            case 'helix':
                if(!$bFullUrl)
                    $strUrl = $this->baseUrl .'helix/';

                // Since all Helix requests require a bearer now
                $oOAuthHandler = new OAuthHandler('twitch');
                $oOAuthSession = $oOAuthHandler->isAuthValid(null, true);

                if($oOAuthSession)
                    $aRequestHeaders['Authorization'] = 'Bearer '. $oOAuthSession->access_token; 
            break;

            case 'kraken':
                if(!$bFullUrl)
                    $strUrl = $this->baseUrl .'kraken/';

                $aRequestHeaders['Accept'] = 'application/vnd.twitchtv.v5+json';
            break;
        }

        $oGuzzle = new Client([
            'headers' => !empty($aHeaders) ? array_merge($aRequestHeaders, $aHeaders) : $aRequestHeaders
        ]);

        try
        {
            $res = $oGuzzle->request('GET', $bFullUrl ? $strEndpoint : $strUrl . $strEndpoint);
            if($res->getStatusCode() == 200)
            {
                if(isset($res->getHeader('Ratelimit-Remaining')[0]) && $res->getHeader('Ratelimit-Remaining')[0] == 0)
                {
                    Log::error('Twitch rate limit reached');
                    throw new Exception('please try again later');
                }

                return json_decode($res->getBody());
            }
            else
                throw new Exception('Unexpected Twitch response');
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            Log::error($e);
            throw new Exception('Twitch error, please try again later');            
        }
    }
}
?>
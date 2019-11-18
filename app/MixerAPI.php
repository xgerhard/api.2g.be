<?php

namespace App;

use Exception;
use GuzzleHttp\Client;
use Log;

class MixerAPI
{
    private $baseUrl = 'https://mixer.com/api';

    public function getChannelFollowers($iChannelId, $aWhere = [])
    {
        $strWhere = '';
        foreach($aWhere as $str => $val)
            $strWhere .= $str .':'. urlencode($val) .',';

        return $this->request('/v1/channels/'. (int) $iChannelId .'/follow'. ($strWhere == '' ? '' : '?where='. substr($strWhere, 0, -1)));
    }

    /**
     * Fire requests with Guzzle
     *
     * @param string $strEndpoint Url or endpoint to retrieve
     * @param array $aHeaders Optional request headers
     *
     * @return json Mixer data response
     */
    private function request($strEndpoint)
    {
        $aRequestHeaders = [];
        $oGuzzle = new Client([
            //'http_errors' => false, 
            'verify' => false,
            'headers' => $aRequestHeaders
        ]);

        try
        {
            $res = $oGuzzle->request('GET', $this->baseUrl . $strEndpoint);
            if($res->getStatusCode() == 200)
                return json_decode($res->getBody());
            else
                throw new Exception('Unexpected Mixer response');
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            Log::error($e);
            throw new Exception('Twitch error, please try again later');            
        }
    }
}
?>
<?php

namespace App;

use Exception;
use Cache;
use GuzzleHttp\Client;
use Log;

class SplitgateTrackerAPI
{
    /**
     * Info: To get more information append /segments/{segmentType} to the profile url.
     * Where segmentType = playlist, map, gamemode, weapon. To filter between social and ranked add the query parameter ?queue=ranked (or social) leaving it out means you will get combined results. For all segments besides weapon, you can also add ?season=10 (Beta Season). When the game launches 11 will be Season 1.
     * One thing to note, the weapon segments contain stats that are based on time. They are currently bugged because of issues during the Beta. So I don't recommend using them.
     */
    private $baseUrl = 'https://public-api.tracker.gg/v2/splitgate/standard/';

    public function getPlayer($strPlayer, $strPlatform)
    {
        return $this->request('profile/'. $strPlatform .'/'. rawurlencode($strPlayer));
    }

    public function getPlayerSegment($strPlayer, $strPlatform, $strSegmentType)
    {
        return $this->request('profile/'. $strPlatform .'/'. rawurlencode($strPlayer) .'/segments/'. $strSegmentType);
    }

    private function request($strEndpoint, $bFullUrl = false, $aHeaders = [])
    {
        $aRequestHeaders = [
            'TRN-Api-Key' => ENV('TRN_APEX_API_KEY')
        ];

        if(!empty($aHeaders))
            $aRequestHeaders = array_merge($aRequestHeaders, $aHeaders);

        $oGuzzle = new Client([
            'http_errors' => false, 
            'verify' => false,
            'headers' => $aRequestHeaders
        ]);

        try
        {
            $res = $oGuzzle->request('GET', $bFullUrl ? $strEndpoint : $this->baseUrl . $strEndpoint);
            if($res->getStatusCode() == 200)
                return json_decode($res->getBody());
            else if($res->getStatusCode() == 404)
                return false;
            else
                throw new Exception('Unexpected API response');
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            Log::error($e);
            throw new Exception('API error, please try again later');            
        }
    }
}
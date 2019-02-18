<?php

namespace App;

use Exception;
use Cache;
use GuzzleHttp\Client;

class ApexTrackerAPI
{
    private $baseUrl = 'https://public-api.tracker.gg/apex/v1/standard/';

    public function getPlayer($strPlayer, $iPlatform)
    {
        return $this->request('profile/'. $iPlatform .'/'. rawurlencode($strPlayer));
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
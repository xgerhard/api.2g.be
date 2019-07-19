<?php

namespace App;

use Exception;
use Cache;
use GuzzleHttp\Client;
use Log;

class LeagueOfLegendsAPI
{
    private $baseUrl;

    public function __construct($strRegion)
    {
        $aRegions = [
            'br' => 'br1',
            'eune' => 'eun1',
            'euw' => 'euw1',
            'jp' => 'jp1',
            'kr' => 'kr',
            'lan' => 'la1',
            'las' => 'la2',
            'na' => 'na1',
            'oce' => 'oc1',
            'tr' => 'tr1',
            'ru' => 'ru',
            'pbe' => 'pbe1'
        ];

        $this->baseUrl = 'https://'. $aRegions[$strRegion] .'.api.riotgames.com/lol/';
    }

    public function getSummoner($strSummoner)
    {
        return $this->request('summoner/v4/summoners/by-name/'. rawurlencode($strSummoner));
    }

    private function request($strEndpoint, $bFullUrl = false, $aHeaders = [])
    {
        $aRequestHeaders = [
            'X-Riot-Token' => ENV('RIOT_LOL_API_KEY')
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
            elseif($res->getStatusCode() == 404)
                return false;
            else
            {
                echo $res->getStatusCode();
                //throw new Exception('Unexpected API response');
            }
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            Log::error($e);
            throw new Exception('API error, please try again later');            
        }
    }
}
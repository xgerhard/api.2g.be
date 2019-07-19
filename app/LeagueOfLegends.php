<?php

namespace App;

use App\LeagueOfLegendsAPI;
use Exception;
use Cache;

class LeagueOfLegends
{
    public function get($strAction, $strSummoner, $strRegion)
    {
        $oPlayer = false;
        if($strSummoner && $strRegion)
        {
            $strCacheKey = 'lol-player-'. $strSummoner .'-'. $strRegion;
            if(Cache::has($strCacheKey))
                $oPlayer = Cache::get($strCacheKey);
            else
            {
                $aPlatforms = ['ps' => 2, 'xbox' => 1, 'pc' => 5];
                $oAPI = new LeagueOfLegendsAPI($strRegion);
                $oPlayer = $oAPI->getSummoner($strSummoner);

                echo '<pre>';
                print_r($oPlayer);

                if($oPlayer && isset($oPlayer->error))
                    return 'Error: '. $oPlayer->error;
                else if(!$oPlayer || !isset($oPlayer->data))
                    return 'Summoner: '. $strSummoner .' ('. strtoupper($strRegion) .') not found';
                //else
                    //Cache::put($strCacheKey, $this->dataCleaner($oPlayer), 5);
            }
        }
    }
}
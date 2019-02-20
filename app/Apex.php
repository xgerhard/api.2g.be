<?php

namespace App;

use App\ApexTrackerAPI;
use Exception;
use Cache;

class Apex
{
    public function get($strAction, $strPlayer, $strPlatform)
    {
        $oPlayer = false;
        if($strPlayer && $strPlatform)
        {
            $strCacheKey = 'player-'. $strPlayer .'-'. $strPlatform;
            if(Cache::has($strCacheKey))
                $oPlayer = Cache::get($strCacheKey);
            else
            {
                $aPlatforms = ['ps' => 2, 'xbox' => 1, 'pc' => 5];
                $oAPI = new ApexTrackerAPI;
                $oPlayer = $oAPI->getPlayer($strPlayer, $aPlatforms[$strPlatform]);

                if(!$oPlayer || !isset($oPlayer->data))
                    return 'Player: '. $strPlayer .' not found';
                else
                    Cache::put($strCacheKey, $this->dataCleaner($oPlayer), 5);
            }
        }

        $aHeroes = ['bloodhound', 'gibraltar', 'lifeline', 'pathfinder', 'wraith', 'bangalore', 'caustic', 'mirage'];
        if(in_array($strAction, $aHeroes))
        {
            $strHero = $strAction;
            $strAction = 'hero';
        }

        switch($strAction)
        {
            case 'info':
            default:
                return 'Usage: "!apex action username platform" - [Available actions: stats, {hero name}] [Note: Apex.tracker.gg can only update the legend currently active on your banner]';
            break;

            case 'profile':
                if(!$oPlayer)
                    return 'No player and/or platform provided';

                $aPlatforms = ['ps' => 'psn', 'xbox' => 'xbl', 'pc' => 'pc'];
                return 'View all stats from "'. $strPlayer .'" at https://apex.tracker.gg/profile/'. $aPlatforms[$strPlatform] .'/'. rawurldecode($strPlayer);
            break;

            case 'stats':
                if(!$oPlayer)
                    return 'No player and/or platform provided';
  
                $oPlayer = $oPlayer->data;
                $strRes = (isset($oPlayer->metadata->platformUserHandle) ? $oPlayer->metadata->platformUserHandle .': ' : '');

                if(isset($oPlayer->stats) && !empty($oPlayer->stats))
                {
                    $aTempStats = [];
                    foreach($oPlayer->stats as $oStat)
                    {
                        if($oStat->displayValue > 0)
                            $aTempStats[] = $oStat->metadata->name .': '. $oStat->displayValue;
                    }

                    if(!empty($aTempStats))
                        $strRes .= '['. implode(' | ', $aTempStats) .']';
                }
                return $strRes;
            break;

            case 'hero':
                if(!$oPlayer)
                    return 'No player and/or platform provided';

                $oPlayer = $oPlayer->data;
                $strRes = (isset($oPlayer->metadata->platformUserHandle) ? $oPlayer->metadata->platformUserHandle .': ' : '');
                $bFound = false;

                if(isset($oPlayer->children) && !empty($oPlayer->children))
                {
                    $aTempStats = [];
                    foreach($oPlayer->children as $oChild)
                    {
                        if($strHero == strtolower($oChild->metadata->legend_name) && isset($oChild->stats) && !empty($oChild->stats))
                        {
                            foreach($oChild->stats as $oStat)
                            {
                                if($oStat->displayValue > 0)
                                    $aTempStats[] = $oStat->metadata->name .': '. $oStat->displayValue;
                            }

                            if(!empty($aTempStats))
                            {
                                $strRes .= ucfirst($strHero). ': ['. implode(' | ', $aTempStats) .']';
                                $bFound = true;
                            }
                            break;
                        }
                    }
                }
                return $bFound ? $strRes : '"'. ucfirst($strHero) .'" hero stats not found';
            break;
        }
    }

    // We're caching the data so let's clean it up
    private function dataCleaner($oResponse)
    {
        unset($oResponse->data->id, $oResponse->data->type);
        if(isset($oResponse->data->children) && !empty($oResponse->data->children))
        {
            foreach($oResponse->data->children as $i => $oChild)
            {
                unset($oChild->id, $oChild->type);
                if(isset($oChild->metadata))
                    unset($oChild->metadata->icon, $oChild->metadata->bgimage);

                if(isset($oChild->stats) && !empty($oChild->stats))
                {
                    foreach($oChild->stats as $y => $oStat)
                    {
                        $oChild->stats[$y] = (object) [
                            'metadata' => (object) ['name' => $oStat->metadata->name],
                            'displayValue' => $oStat->displayValue
                        ];
                    }
                }
            }
        }

        if(isset($oResponse->data->metadata))
            $oResponse->data->metadata = (object) ['platformUserHandle' => $oResponse->data->metadata->platformUserHandle];

        if(isset($oResponse->data->stats) && !empty($oResponse->data->stats))
        {
            foreach($oResponse->data->stats as $j => $oStat)
            {
                $oResponse->data->stats[$j] = (object) [
                    'metadata' => (object) ['name' => $oStat->metadata->name],
                    'displayValue' => $oStat->displayValue
                ];
            }
        }
        return $oResponse;
    }
}
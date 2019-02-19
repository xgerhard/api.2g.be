<?php

namespace App;

use App\ApexTrackerAPI;
use Exception;
use Cache;

class Apex
{
    public function get($strAction, $strPlayer, $strPlatform)
    {
        if($strPlayer && $strPlatform)
        {
            $aPlatforms = ['ps' => 1, 'xbox' => 2, 'pc' => 5];
            $oAPI = new ApexTrackerAPI;
            $oPlayer = $oAPI->getPlayer($strPlayer, $aPlatforms[$strPlatform]);
            if(!$oPlayer || !isset($oPlayer->data))
                return 'Player: '. $strPlayer .' not found';
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

            case 'stats':
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
}
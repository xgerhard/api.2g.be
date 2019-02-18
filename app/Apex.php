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

        switch($strAction)
        {
            case 'info':
            default:
                return 'Usage: "!apex action username platform" - [Available actions: stats]';
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
        }
    }
}
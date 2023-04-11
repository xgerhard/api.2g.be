<?php

namespace App;

use App\ApexTrackerAPI;
use Exception;
use Cache;

class Splitgate
{
    private $api;

    public function __construct()
    {
        $this->api = new SplitgateTrackerAPI;
    }

    public function get($strAction, $strPlayer, $strPlatform)
    {
        $oPlayer = false;
        if($strPlayer && $strPlatform)
        {
            $strCacheKey = 'splitgate-player-'. $strPlayer .'-'. $strPlatform;
            if(Cache::has($strCacheKey))
                $oPlayer = Cache::get($strCacheKey);
            else
            {
                $aPlatforms = ['ps' => 'psn', 'xbox' => 'xbl', 'pc' => 'steam', 'steam' => 'steam'];
                $oPlayer = $this->api->getPlayer($strPlayer, $aPlatforms[$strPlatform]);

                if($oPlayer && isset($oPlayer->error))
                    return 'Error: '. $oPlayer->error; 
                else if(!$oPlayer || !isset($oPlayer->data))
                    return 'Player: '. $strPlayer .' not found';
                else
                {
                    $oPlayer = (object) [
                        'info' => (object) [
                            'displayName' => $oPlayer->data->platformInfo->platformUserHandle,
                            'name' => $oPlayer->data->platformInfo->platformUserIdentifier,
                            'platform' => $oPlayer->data->platformInfo->platformSlug
                        ]
                    ];
                    Cache::put($strCacheKey, $oPlayer, 5);
                }
            }
        }

        switch($strAction)
        {
            case 'info':
            default:
                return 'Usage: "!splitgate action username platform" - [Available actions: overall, social, ranked, 4x4, takedown]';
            break;

            case 'profile':
                if(!$oPlayer)
                    return 'No player and/or platform provided';

                return 'View all stats from "'. $oPlayer->info->displayName .'" at https://tracker.gg/splitgate/profile/'. $oPlayer->info->platform .'/'. rawurldecode($oPlayer->info->name);
            break;

            case 'stats':
                if(!$oPlayer)
                    return 'No player and/or platform provided';
  
                $oPlaylist = $this->api->getPlayerSegment($oPlayer->info->name, $oPlayer->info->platform, 'playlist');

                if($oPlaylist && isset($oPlaylist->data))
                {
                    $aPlaylists = explode(',', strtolower(request('playlists', 'ranked_team_hardcore,ranked_team_takedown')));
                    $aFields = explode(',', strtolower(request('fields', 'kills,kd,wins,wlPercentage')));
                    $bMerged = request('merged', 'false') === 'true' ? true : false;

                    $aData = $this->filterResults($oPlaylist->data, $aPlaylists, $aFields, $bMerged);
                }

                $strRes = $oPlayer->info->displayName .': ';

                if(!empty($aData))
                {
                    $aTempPlaylists = [];
                    foreach($aData as $strPlaylist => $oPlaylist)
                    {
                        $aTempStats = [];
                        foreach($oPlaylist->stats as $oStat)
                        {
                            $aTempStats[] = $oStat->displayName .': '. $oStat->displayValue;
                        }

                        if(!empty($aTempStats))
                        {
                            if($oPlaylist->id == 'merged')
                                $aTempPlaylists[] = '['. implode(' | ', $aTempStats) .'] [Merged: '. implode(', ', $oPlaylist->playlists) .']';
                            else
                                $aTempPlaylists[] = $oPlaylist->title .': ['. implode(' | ', $aTempStats) .']';
                        }
                    }

                    if(!empty($aTempPlaylists))
                        $strRes .= implode(', ', $aTempPlaylists);
                }
                else $strRes .= 'No stats found';

                return $strRes;
            break;
        }
    }

    private function filterResults($aPlaylistsData, $aPlaylists, $aFields, $bMerge)
    {
        $aData = [];
        if(!empty($aPlaylistsData))
        {
            if($bMerge)
            {
                // Overall data.. we need to recalculate certain fields, like win % or K/D.
                $aExtraFields = ['kills', 'deaths', 'assists', 'wins', 'losses', 'timeplayed', 'kd', 'kad'];
                $aUnwantedFields = array_diff($aExtraFields, $aFields);
                $aFields = array_merge($aFields, $aExtraFields);
            }

            foreach($aPlaylistsData as $oPlaylistdata)
            {
                if(in_array(strtolower($oPlaylistdata->attributes->key), $aPlaylists))
                {
                    $aTempPlaylist = (object) [
                        'id' => $oPlaylistdata->attributes->key,
                        'title' => $oPlaylistdata->metadata->name,
                        'stats' => []
                    ];

                    foreach($oPlaylistdata->stats as $strField => $oStat)
                    {
                        if(in_array(strtolower($strField), $aFields))
                        {
                            $aTempPlaylist->stats[strtolower($strField)] = (object) [
                                'value' => $oStat->value,
                                'displayName' => $oStat->displayName,
                                'displayValue' => $oStat->displayValue,
                                'displayType' => $oStat->displayType
                            ];
                        }
                    }

                    $aData[strtolower($oPlaylistdata->attributes->key)] = $aTempPlaylist;
                }
            }

            if($bMerge)
            {
                $aTempPlaylist = (object) [
                    'id' => 'merged',
                    'title' => 'merged',
                    'playlists' => [],
                    'stats' => []
                ];

                // Merge playlists
                $aMerged = [];
                foreach($aData as $strPlaylistIdentifier => $oPlaylistStats)
                {
                    $aTempPlaylist->playlists[] = $oPlaylistStats->title;
                    foreach($oPlaylistStats->stats as $strStatIdentifier => $oStat)
                    {
                        if(isset($aMerged[$strStatIdentifier]))
                        {
                            $aMerged[$strStatIdentifier]->value += $oStat->value;
                        }
                        else
                        {
                            $aMerged[$strStatIdentifier] = clone $oStat;
                        }
                    }
                }

                // Correct merged fields 
                if(!empty($aMerged))
                {
                    if(isset($aMerged['kills']) && isset($aMerged['deaths']))
                    {
                        if(isset($aMerged['kd']))
                        {
                            $aMerged['kd']->value = $aMerged['kills']->value / $aMerged['deaths']->value;
                        }

                        if(isset($aMerged['assists']) && isset($aMerged['kad']))
                        {
                            $aMerged['kad']->value = ($aMerged['kills']->value + $aMerged['assists']->value) / $aMerged['deaths']->value;
                        }
                    }

                    if(isset($aMerged['wins']) && isset($aMerged['losses']) && isset($aMerged['wlpercentage']))
                    {
                        $aMerged['wlpercentage']->value =  ($aMerged['wins']->value / ($aMerged['wins']->value + $aMerged['losses']->value)) * 100;
                    }

                    foreach($aMerged as $strStatIdentifier => $oStat)
                    {
                        $oStat->displayValue = $this->formatData($oStat->value, $oStat->displayType);
                    }

                    if(!empty($aUnwantedFields))
                    {
                        foreach($aUnwantedFields as $strUnwantedField)
                        {
                            unset($aMerged[$strUnwantedField]);
                        }
                    }                    
                }

                $aData = [];
                $aTempPlaylist->stats = $aMerged;
                $aData['merged'] = $aTempPlaylist;
            }
        }
        return $aData;
    }

    private function formatData($data, $strDisplayType)
    {
        switch($strDisplayType)
        {
            case 'TimeSeconds':
                \Carbon\CarbonInterval::setCascadeFactors([
                    'minute' => [60, 'seconds'],
                    'hour' => [60, 'minutes'],
                ]);
                $aCarbon = \Carbon\CarbonInterval::seconds($data)->cascade();

                $str = '';
                if($aCarbon->h > 0)
                    $str .= $aCarbon->h .'h ';

                if($aCarbon->i > 0)
                    $str .= $aCarbon->i .'m ';

                if($aCarbon->s > 0)
                    $str .= $aCarbon->s .'s';

                return trim($str);
            break;

            case 'NumberPrecision2':
                return number_format($data, 2, '.', ',');
            break;

            case 'NumberPercentage':
                return number_format($data, 2, '.', ',') .'%';
            break;

            case 'Number':
                return number_format($data, 0, '', ',');    
            break;

            default:
                return $data;
            break;
        }
    }
}
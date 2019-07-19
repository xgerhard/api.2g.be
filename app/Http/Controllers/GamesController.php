<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use xgerhard\nbheaders\Nightbot;
use Exception;
use App\Twitch;
use App\Apex;
use App\LeagueOfLegends;
use Log;

class GamesController extends Controller
{
    public $games = [
        'apex' => [
            'query' => ['action', 'user.fill', 'platform'],
            'title' => 'apex.tracker.gg'
        ],
        'LeagueOfLegends' => [
            'query' => ['action', 'summoner.fill', 'region']
        ]
    ];

    private function parseQuery($aQuery, $aPattern, $bReverse = false)
    {
        foreach($aPattern as $iPosition => $strPattern)
        {
            if(!empty($aQuery) && isset($aQuery[$iPosition]))
            {
                if(strpos($strPattern, '.fill') !== false)
                {
                    // If reversed and reached the end, merge all the values that are left to fill the .fill parameter
                    if($bReverse)
                        $this->{str_replace('.fill', '', $strPattern)} = implode(' ', array_reverse($aQuery));

                    break;
                }

                if($strValue = $this->filterPass($strPattern, $aQuery[$iPosition]))
                {
                    $this->{$strPattern} = $strValue;
                    unset($aQuery[$iPosition]);
                }
            }
        }
        return $aQuery;
    }

    public function __construct(Request $request)
    {
        if(isset($request->route()->getAction()['game']))
            $this->game = $request->route()->getAction()['game'];
        else
            throw new Exception('invalid route');

        if($request->has('q') && isset($this->games[$this->game]['query']))
        {
            $aQuery = explode(' ', trim($request->get('q')));
            $aQuery = array_values(array_diff($aQuery, ['']));
            $aPattern = $this->games[$this->game]['query'];

            // Use the pattern set in $this->games to parse the query
            $this->parseQuery(
                array_reverse(
                    $this->parseQuery($aQuery, $aPattern)
                ),
                array_reverse($aPattern),
                true
            );
        }
    }

    public function run()
    {
        try
        {
            switch($this->game)
            {
                case 'apex';
                    $oApex = new Apex;
                    return $this->formatText($oApex->get(
                        isset($this->action) ? $this->action : 'info',
                        isset($this->user) ? $this->user : null,
                        isset($this->platform) ? $this->platform : (isset($this->default_platform) ? $this->default_platform : null)
                    ));
                break;

                case 'LeagueOfLegends':
                    $oLoL = new LeagueOfLegends;
                    return $this->formatText($oLoL->get(
                        isset($this->action) ? $this->action : 'info',
                        isset($this->summoner) ? $this->summoner : null,
                        isset($this->region) ? $this->region : (isset($this->default_region) ? $this->default_region : null)
                    ));
                break;
            }
        }
        catch(Exception $e)
        {
            Log::error($e);
            return $e->getMessage();         
        }
    }

    private function formatText($strRes)
    {
        if(rand(0,3) == 0 && isset($this->games[$this->game]['title']))
            $strRes .= ' ['. $this->games[$this->game]['title'] .' ❤]';

        return substr($strRes, 0, 400);
    }

    public function filterPass($strKey, $strValue)
	{
        switch($strKey)
        {
            case 'region':
                $strValue = trim(strtolower($strValue));
                $aRegions = ['br', 'eune', 'euw', 'lan', 'na', 'oce', 'ru', 'tr', 'jp', 'sea', 'kr', 'cn', 'pbe'];

                if(in_array($strValue, $aRegions))
                    return $strValue;
                else
                    return false;
            break;

            case 'platform':
                $strValue = trim(strtolower($strValue));
                $a = [
                    'xbox' => ['xbl', 'xbox', 'xb1'],
                    'ps' => ['ps', 'playstation', 'ps4'],
                    'pc' => ['pc', 'origin']
                ];

                foreach($a as $strConsoleKey => $aConsoles)
                {
                    if(in_array($strValue, $aConsoles))
                        return $strConsoleKey;
                }
                return false;
            break;
        }
        return $strValue;
    }
}
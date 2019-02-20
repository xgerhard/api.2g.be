<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use xgerhard\nbheaders\Nightbot;
use Exception;
use App\Twitch;
use App\Apex;
use Log;

class GamesController extends Controller
{
    public $game;
    public $action;
    public $user;
    public $games = [
        'apex' => [
            'query' => ['action', 'user', 'platform'],
            'title' => 'apex.tracker.gg'
        ]
    ];

    public function __construct(Request $request)
    {
        if(isset($request->route()->getAction()['game']))
            $this->game = $request->route()->getAction()['game'];
        else
            throw new Exception('invalid route');

        if($request->has('q'))
        {
            $strQuery = trim($request->get('q'));
            $aQuery = explode(' ', $strQuery);
            $aQuery = array_diff($aQuery, array(''));

            if(!empty($aQuery))
                $this->action = strtolower(trim(array_shift($aQuery)));

            if(!empty($aQuery) && $this->isValidConsole(end($aQuery)))
                $this->platform = $this->isValidConsole(array_pop($aQuery));

            if(!empty($aQuery))
                $this->user = implode(' ', $aQuery);
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
        if(rand(0,3) == 0)
            $strRes .= ' ['. $this->games[$this->game]['title'] .' ❤]';

        return substr($strRes, 0, 400);
    }

    public function isValidConsole($strConsole)
	{
        $strConsole = trim(strtolower($strConsole));
        $a = array(
            'xbox' => array('xbl', 'xbox', 'xb1'),
            'ps' => array('ps', 'playstation', 'ps4'),
            'pc' => array('pc', 'origin')
        );

        foreach($a as $strConsoleKey => $aConsoles)
        {
            if(in_array($strConsole, $aConsoles))
                return $strConsoleKey;
        }
        return false;
	}
}
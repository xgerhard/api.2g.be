<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use xgerhard\nbheaders\Nightbot;
use Exception;
use App\Twitch;

class GamesController extends Controller
{
    public function __construct(Request $request)
    {
        if(isset($request->route()->getAction()['game']))
            $strGame = $request->route()->getAction()['game'];
        else
            throw new Exception('invalid route');

        // Define order of variables in query
        $aGames = [
            'apex' => [
                'query' => ['action', 'user', 'platform']
            ]
        ];

        $oTwitch = new Twitch;
        //$aUsers = $oTwitch->getUsers(['xgerhard', 'xgerhard'], new Nightbot($request));
        //echo '<pre>';
        //print_r($aUsers);

        if($request->has('q'))
        {
            $strQuery = trim($request->get('q'));
        }
    }

    public function run()
    {

    }
}
<?php

namespace App;

use App\TwitchAPI;
use Exception;
use Cache;

class Twitch
{
    public function __construct()
    {
        $this->twitchAPI = new TwitchAPI;
    }

    public function getUsers($aUsers = [], $oNightbot = null)
    {
        $aReturnUsers = [];
        if(!empty($aUsers))
        {
            // Check if Id is already sent by Nightbot headers
            $aNightbotUser = $oNightbot->getUser();
            $aNighbotChannel = $oNightbot->getChannel();

            foreach($aUsers as $i => $strUser)
            {
                // Grab Nightbot user data if available
                if($aNightbotUser && (($i == 1 && !isset($aUsers[1])) || $aNightbotUser['name'] == $strUser))
                {
                    $aReturnUsers[$i] = (object) [
                        'id' => $aNightbotUser['providerId'],
                        'name' => $aNightbotUser['name'],
                        'displayName' => $aNightbotUser['displayName']
                    ];
                    continue;
                }

                // Grab Nightbot channel data if available
                if($aNighbotChannel && (($i == 0 && !isset($aUsers[0])) || $aNighbotChannel['name'] == $strUser))
                {
                    $aReturnUsers[$i] = (object) [
                        'id' => $aNighbotChannel['providerId'],
                        'name' => $aNighbotChannel['name'],
                        'displayName' => $aNighbotChannel['displayName']
                    ];
                    continue;
                }

                // Check if user exist in local cache
                $strCacheKey = 'twitch-users-'. strtolower($strUser);
                if(cache::has($strCacheKey))
                {
                    $aReturnUsers[$i] = cache::get($strCacheKey);
                    continue;
                }

                // Can't search for null
                if(!$strUser) throw new Exception('Channel or user parameter missing');

                // If not found, add it to array that we will request to Twitch
                $aSearchUsers[$i] = $strUser;
            }
        }

        if(!empty($aSearchUsers))
        {
            $oUserSearch = $this->twitchAPI->searchUsers($aSearchUsers);
            if($oUserSearch && $oUserSearch->_total > 0)
            {
                foreach($oUserSearch->users as $oUser)
                {
                    $i = array_search($oUser->name, $aSearchUsers); 
                    $aReturnUsers[$i] = (object) [
                        'id' => $oUser->_id,
                        'name' => $oUser->name,
                        'displayName' => $oUser->display_name
                    ];

                    Cache::put('twitch-users-'. strtolower($oUser->name), $aReturnUsers[$i], 86400);
                    unset($aSearchUsers[$i]);
                }
            }
        }

        // We unset the searchUsers when found, if the array isn't empty yet, it doesn't exist
        if(!empty($aSearchUsers))
            throw new Exception('User'. (count($aSearchUsers) == 1 ? '' : 's') .' not found:'. implode($aSearchUsers));

        return $aReturnUsers;
    }
}
?>
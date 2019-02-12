<?php

namespace App;

use App\TwitchAPI;
use Exception;
use Cache;

class Twitch
{
    private $userCacheLength = 86400;

    public function __construct()
    {
        $this->twitchAPI = new TwitchAPI;
    }

    public function getUsers($aUsers = [], $oNightbot = null)
    {
        $aReturnUsers = [];
        if(!empty($aUsers))
        {
            $aNightbotUser = $oNightbot->getUser();
            if($aNightbotUser)
            {
                if($aNightbotUser['provider'] == 'twitch')
                {
                    $oUser = (object) [
                        'id' => $aNightbotUser['providerId'],
                        'name' => $aNightbotUser['name'],
                        'displayName' => $aNightbotUser['displayName']
                    ];
                    Cache::put('twitch-users-'. strtolower($oUser->name), $oUser, $this->userCacheLength);

                    // Set user by Nightbot headers if the user parameter is Null ($aUsers[1])
                    if(array_key_exists(1, $aUsers) && (!$aUsers[1] || $aUsers[1] == $oUser->name))
                        $aReturnUsers[1] = $oUser;
                }
                // In case the user isn't from Twitch, we can still search his username, they might be the same on the other platform
                elseif(array_key_exists(1, $aUsers) && !$aUsers[1])
                    $aUsers[1] = $aNightbotUser['displayName'];
            }

            $aNighbotChannel = $oNightbot->getChannel();
            if($aNighbotChannel && $aNighbotChannel['provider'] == 'twitch')
            {
                $oUser = (object) [
                    'id' => $aNighbotChannel['providerId'],
                    'name' => $aNighbotChannel['name'],
                    'displayName' => $aNighbotChannel['displayName']
                ];
                Cache::put('twitch-users-'. strtolower($oUser->name), $oUser, $this->userCacheLength);

                // Set channel by Nightbot headers if the channel parameter is Null ($aUsers[0])
                if(array_key_exists(0, $aUsers) && (!$aUsers[0] || $aUsers[0] == $oUser->name))
                    $aReturnUsers[0] = $oUser;
            }

            foreach($aUsers as $i => $strUser)
            {
                if($strUser)
                {
                    // Check if user exist in local cache
                    // If not found, add it to array that we will request to Twitch
                    $strCacheKey = 'twitch-users-'. strtolower($strUser);
                    if(cache::has($strCacheKey))
                        $aReturnUsers[$i] = cache::get($strCacheKey);
                    else
                        $aSearchUsers[$i] = $strUser;
                }
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

                    Cache::put('twitch-users-'. strtolower($oUser->name), $aReturnUsers[$i], $this->userCacheLength);
                    unset($aSearchUsers[$i]);
                }
            }
        }

        // We unset the searchUsers when found, if the array isn't empty yet, it doesn't exist
        if(!empty($aSearchUsers))
            throw new Exception('User'. (count($aSearchUsers) == 1 ? '' : 's') .' not found: '. implode(', ', $aSearchUsers));

        // If we failed to find the correct user or channel, throw exception
        if(count($aUsers) > count($aReturnUsers))
             throw new Exception('Channel or user parameter missing');

        return $aReturnUsers;
    }
}
?>
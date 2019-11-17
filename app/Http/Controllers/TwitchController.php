<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TwitchAPI;
use App\Twitch;
use App\helpers\TimeDifference;
use Exception;

class TwitchController extends Controller
{
    public function __construct()
    {
        $this->twitch = new Twitch;
        $this->twitchAPI = new TwitchAPI;
    }

    public function followAge(Request $request, $channel = null, $user = null)
    {
        try
        {
            if($channel)
            {
                $channel = preg_replace('/[^a-zA-Z\d_]+/i', '', urldecode($channel));
                if(strlen($channel) > 25)
                    return 'Error: Invalid channel';
            }

            if($user)
            {
                $user = preg_replace('/[^a-zA-Z\d_]+/i', '', urldecode($user));
                if(strlen($user) > 25)
                    return 'Error: Invalid user';
            }

            // Set date format
            $strFormat = 'd-m-Y H:i:s';
            if($request->has('format'))
            {
                $strFormat = $request->get('format');
                if(is_string($strFormat))
                {
                    $strFormat = urldecode($strFormat);
                    if($strFormat != 'mwdhms') // Since 99% of the commands use this
                    {
                        $strFormat = preg_replace('/[^a-z.\-\\\_: ()]+/i', '', $strFormat);
                        if(trim($strFormat) == '' || strlen(str_replace(' ', '', $strFormat)) > 15)
                            return 'Error: Invalid date format';
                        else
                        {
                            // Whooo lets write a repeater check & skip text out
                            $iMaxRepeat = 3;
                            $aTempDate = [];
                            $strTempDate = '';
                            $bSkip = false;

                            foreach(str_split($strFormat) as $s)
                            {
                                if($bSkip == true)
                                {
                                    $bSkip = false;
                                    continue;
                                }
                                elseif($s == '\\')
                                {
                                    $bSkip = true;
                                    continue;
                                }

                                if(isset($aTempDate[$s]))
                                {
                                    $aTempDate[$s]++;
                                    if($aTempDate[$s] > $iMaxRepeat)
                                        continue;
                                }
                                else
                                    $aTempDate[$s] = 1;

                                $strTempDate .= $s;
                            }

                            if(trim($strTempDate) == '')
                                return 'Error: Invalid date format';

                            $strFormat = $strTempDate;
                        }
                    }
                }
                else
                    return 'Error: Invalid date format';
            }

            // We dont want users to set the format field
            if(isset($_SERVER['QUERY_STRING']) && substr_count(strtolower($_SERVER['QUERY_STRING']), 'format') > 1)
                return 'Error: Invalid request';

            $aUsers = $this->twitch->getUsers([$channel, $user]);
            $oChannel = $aUsers[0];
            $oUser = $aUsers[1];
            $oTimeDifference = new TimeDifference;
            $bSince = true; // follow since or for

            // Set timezone
            $strTimezone = 'UTC';
            if($request->has('timezone') && $oTimeDifference->isValidTimezone(urldecode($request->get('timezone'))))
                $strTimezone = urldecode($request->get('timezone'));

            // No leading text
            $bNoText = false;
            if($request->has('notext'))
                $bNoText = true;

            // Custom response
            $aCustom = [
                // 187982262 = ChickenNuggetz_
                49056910 => '1337 Kappa', // xgerhard
                67031397 => '69 Kreygasm', // brownbear0
                30093211 => '420 CiGrip' // fredriksjoqvist
            ];

            if(isset($aCustom[$oUser->id]))
                return (strpos($strFormat, 'int') !== false ? '' : $oUser->displayName .' has been following '. $oChannel->displayName .' for ') . $aCustom[$oUser->id] .' ';
            elseif($oUser->id == 166810102) // 5UCC
                $strFormat = 'l';

            // Fetch follower data
            $oFollowCheck = $this->twitchAPI->getUsersFollows($oUser->id, $oChannel->id);
            if($oFollowCheck && isset($oFollowCheck->total))
            {
                if($oFollowCheck->total == 0)
                    return $oUser->displayName .' is not following '. $oChannel->displayName;
                elseif($oFollowCheck->total == 1)
                {
                    $iFrom = $oTimeDifference->parseDate($oFollowCheck->data[0]->followed_at, $strTimezone);
                    $iTo = $oTimeDifference->now;
                    $strDateFormat = false;

                    switch($strFormat)
                    {
                        case 'dmy';
                            $strDateFormat = 'd-m-Y';
                        break;

                        case 'mdy';
                            $strDateFormat = 'm-d-Y';
                        break;

                        case 'dfy':
                            $strDateFormat = 'F j, Y';
                        break;

                        case 'ljsfy':
                            $strDateFormat = 'l jS \of F Y';
                        break;

                        case 'mdygia':
                            $strDateFormat = 'm-d-Y, g:i A';      
                        break;

                        case 'days':
                        case 'daysint':
                            $bSince = false;
                            $iDays = floor(($iTo-$iFrom)/(60*60*24));
                            if($strFormat == 'daysint') 
                            {
                                $strRes = $iDays;
                                $bNoText = true;
                            }
                            else
                                $strRes = $iDays .' day' . ($iDays == 1 ? '' : 's');
                        break;

                        case 'months':
                        case 'monthsint':
                            $bSince = false;
                            $iMonths = ((date('Y', $iTo) - date('Y', $iFrom)) * 12) + (date('m', $iTo) - date('m', $iFrom));
                            if($strFormat == 'monthsint') 
                            {
                                $strRes = $iMonths;
                                $bNoText = true;
                            }
                            else
                                $strRes = $iMonths .' month' . ($iMonths == 1 ? '' : 's');
                        break;

                        case 'monthday';
                            $bSince = false;
                            $strRes = $oTimeDifference->diff_in_months_and_days($iFrom);
                        break;

                        case 'ymwdhis':
                        case 'mwdhms':
                            $bSince = false;
                            $strRes = $oTimeDifference->time_elapsed_string($iFrom, null, true, $strTimezone);
                        break;

                        case 'ymwd':
                            $bSince = false;
                            $strRes = $oTimeDifference->time_elapsed_string($iFrom, $iTo, false, $strTimezone);
                        break;

                        case 'datewd':
                            $strRes = date('Y-m-d', $iFrom) .' ('. $oTimeDifference->diff_in_weeks_and_days($iFrom, $iTo) .')';
                        break;

                        default:
                            $strDateFormat = $strFormat;
                        break;
                    }

                    if($strDateFormat !== false)
                        $strRes = date($strDateFormat, $iFrom);

                    return ($bNoText === true ? '' : $oUser->displayName .' has '. ($oUser->id == 187982262 ? 'not ': '') .'been following '. $oChannel->displayName .' '. ($bSince === false ? 'for' : 'since') .' ') . $strRes;
                }
            }
            else
                return 'Unexpected Twitch response';
        }
        catch(Exception $e)
        {
            return 'Error: '. $e->getMessage() .'.';
        }
    }

    public function recentFollower(Request $request, $channel = null, $user = null)
    {
        try
        {
            if($channel)
            {
                $channel = preg_replace('/[^a-zA-Z\d_]+/i', '', urldecode($channel));
                if(strlen($channel) > 25)
                    return 'Error: Invalid channel';
            }

            $aUsers = $this->twitch->getUsers([$channel]);
            $oChannel = $aUsers[0];

            // Defaults
            $bRecentFollower = true;
            $iFirst = 1;

            // Pick random followe from latest x results
            if($request->has('x'))
            {
                $bRecentFollower = false;
                $iFirst = (int) $request->get('x');
 
                // Min & max
                if($iFirst == 0)
                    $iFirst = 1;
                elseif($iFirst > 100)
                    $iFirst = 100;
            }
            // How many users to return
            elseif($request->has('count'))
            {
                $iFirst = (int) $request->get('count');

                // Min & max
                if($iFirst == 0)
                    $iFirst = 1;
                if($iFirst > 25)
                    $iFirst = 25;
            }

            // Fetch channel data
            $oFollowers = $this->twitchAPI->getUsersFollows(null, $oChannel->id, null, $iFirst);
            if(isset($oFollowers->data) && !empty($oFollowers->data))
            {
                if($bRecentFollower)
			    {
                    $aDisplayFollowers = [];
                    foreach(array_slice($oFollowers->data, 0, $iFirst) as $oFollower)
                        $aDisplayFollowers[] = $oFollower->from_name;

                    return implode(', ', $aDisplayFollowers);
                }
                else
                    return $oFollowers->data[array_rand($oFollowers->data)]->from_name;
            }
            else
                return 'No followers to pick';
        }
        catch(Exception $e)
        {
            return 'Error: '. $e->getMessage() .'.';
        }
    }
}

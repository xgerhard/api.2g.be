<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TwitchAPI;
use App\Twitch;
use xgerhard\nbheaders\Nightbot;
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
                $channel = trim(str_replace('@', '', $channel));

            if($user)
                $user = trim(str_replace('@', '', $user));

            $aUsers = $this->twitch->getUsers([$channel, $user], new Nightbot($request));
            $oChannel = $aUsers[0];
            $oUser = $aUsers[1];
            $oTimeDifference = new TimeDifference;
            $bSince = true; // follow since or for

            // Set date format
            $strFormat = 'd-m-Y H:i:s';
            if($request->has('format'))
                $strFormat = $request->get('format');

            // Set timezone
            $strTimezone = 'UTC';
            if($request->has('timezone') && $oTimeDifference->isValidTimezone($request->get('timezone')))
                $strTimezone = $request->get('timezone');

            // No leading text
            $bNoText = false;
            if($request->has('notext'))
                $bNoText = true;

            // Custom response
            $aCustom = [
                49056910 => '1337 Kappa', // xgerhard
                67031397 => '69 Kreygasm', // brownbear0
                30093211 => '420 CiGrip' // fredriksjoqvist
            ];

            if(isset($aCustom[$oUser->id]))
                return (strpos($strFormat, 'int') !== false ? '' : $oUser->displayName .' has been following '. $oChannel->displayName .' for ') . $aCustom[$oUser->id] .' ';

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

                    return ($bNoText === true ? '' : $oUser->displayName .' has been following '. $oChannel->displayName .' '. ($bSince === false ? 'for' : 'since') .' ') . $strRes;
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
}

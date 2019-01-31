<?php

namespace App\Helpers;

use DateTime;
use DateTimeZone;

class TimeDifference
{
    public $now;

    public function __construct()
    {
        $this->now = strtotime(date("Y-m-d\TH:i:s\Z"));
    }

    public function diff_in_months_and_days($iTimeFrom, $iTimeTo = null)
    {
        if(!$iTimeTo) $iTimeTo = $this->now;

        $iDaysFrom = date('d', $iTimeFrom);
        $iDaysTo = date('d', $iTimeTo);	
        $iMonthsFrom = date('m', $iTimeFrom);
        $iMonthsTo = date('m', $iTimeTo);
        $iYearFrom = date('Y', $iTimeFrom);
        $iYearTo = date('Y', $iTimeTo);
        $iMonthDif = (($iYearTo - $iYearFrom) * 12) + ($iMonthsTo - $iMonthsFrom);
        $iDateDif = $iDaysTo - $iDaysFrom;

        if($iDateDif < 0)
        {
            --$iMonthDif;
            $iDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $iMonthsFrom, $iYearFrom);
            $iDateDif = ($iDaysInMonth - $iDaysFrom) + $iDaysTo;
        }
        return ($iMonthDif > 0 ? $iMonthDif .' month'. ($iMonthDif != 1 ? 's' : '') .', ' : "") . $iDateDif .' day'. ($iDateDif != 1 ? 's' : '');
    }

    public function diff_in_weeks_and_days($iFrom, $iTo = null)
    {
        if(!$iTo) $iTo = $this->now;

        $a = [];
        $iDay = 24 * 3600;
        $iTo = $iTo + $iDay;
        $iDiff = abs($iTo - $iFrom);
        $iWeeks = floor($iDiff / $iDay / 7);
        $iDays = floor($iDiff / $iDay - $iWeeks * 7);
        if ($iWeeks) $a[] = "$iWeeks Week" . ($iWeeks > 1 ? 's' : '');
        if ($iDays)  $a[] = "$iDays Day" . ($iDays > 1 ? 's' : '');
        return implode(', ', $a);
    }

    public function time_elapsed_string($iFrom, $iTo = null, $bFull = false, $strTimezone)
    {
        if(!$iTo) $iTo = $this->now;

        $now = new DateTime(null, new DateTimeZone($strTimezone));
        $now->setTimestamp($iTo);

        $ago = new DateTime(null, new DateTimeZone($strTimezone));
        $ago->setTimestamp($iFrom);

        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        if($bFull)
        {
            $a = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
        }
        else
        {
            $a = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
            );
        }

        foreach($a as $k => &$v)
        {
            if($diff->$k)
                $v = $diff->$k .' '. $v . ($diff->$k > 1 ? 's' : '');
            else
                unset($a[$k]);
        }
        return $a ? implode(', ', $a) . '' : 'just now';
    }

    public function parseDate($strDate, $strToTimezone = null, $strFromTimezone= null)
    {
        // From timezone
        if(!$strFromTimezone)
            $strFromTimezone = 'UTC';

        $dFrom = new DateTime($strDate, new DateTimeZone($strFromTimezone));

        // Set To timezone
        if($strToTimezone) 
            $dFrom->setTimezone(new DateTimeZone($strToTimezone));

        return strtotime($dFrom->format('Y-m-d H:i:s'));
    }

    public function isValidTimezone($strTimezone) 
    {
        return in_array($strTimezone, timezone_identifiers_list());
    }
}
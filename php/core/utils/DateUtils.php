<?php

namespace MiniPhpRest\core\utils;

use DateInterval;
use DateTime;

class DateUtils
{

    /**
     * Tableau des mois
     */
    private static array $monthsEnToFr = array(
        "January" => "Janvier",
        "February" => "Février",
        "March" => "Mars",
        "April" => "Avril",
        "May" => "Mai",
        "June" => "Juin",
        "July" => "Juillet",
        "August" => "Août",
        "September" => "Septembre",
        "October" => "Octobre",
        "November" => "Novembre",
        "December" => "Décembre"
    );

    /**
     * Tableau des jours
     */
    private static array $daysEnToFr = array(
        "Monday" => "Lundi",
        "Tuesday" => "Mardi",
        "Wednesday" => "Mercredi",
        "Thursday" => "Jeudi",
        "Friday" => "Vendredi",
        "Saturday" => "Samedi",
        "Sunday" => "Dimanche"
    );


    public static function isAfter(DateTime $dtA, DateTime $dtB): bool
    {
        return $dtA > $dtB;

    }

    /**
     * @throws \Exception
     */
    public static function addSeconds(DateTime $dt, int $nbSec): DateTime
    {
        $dtNew = clone $dt;
        return $dtNew->add(new DateInterval("PT".$nbSec."S"));
    }

    /**
     * @throws \DateInvalidOperationException
     * @throws \Exception
     */
    public static function minusSeconds(DateTime $dt, int $nbSec): DateTime
    {
        $dtNew = clone $dt;
        return $dtNew->sub(new DateInterval("PT" . $nbSec . "S"));
    }

    /**
     * @throws \Exception
     */
    public static function addMonths(DateTime $dt, int $nbMonth): DateTime
    {
        $dtNew = clone $dt;
        return $dtNew->add(new DateInterval("P".$nbMonth."M"));
    }

    /**
     * @throws \Exception
     */
    public static function addDays(\DateTime $dt, int $nbDay): DateTime
    {
        $dtNew = clone $dt;
        return $dtNew->add(new DateInterval("P".$nbDay."D"));
    }


    /**
     * @throws \Exception
     */
    public static function atFirstDayOfMonth(\DateTime $date) : DateTime
    {
        return new DateTime($date->format('Y-m-01'));
    }

    /**
     * @throws \Exception
     */
    public static function atLastDayOfMonth(DateTime $date) : DateTime
    {
        return new DateTime($date->format('Y-m-t'));
    }

    /**
     * @throws \Exception
     */
    public static function atStartOfDay(DateTime $date) : DateTime
    {
        return new DateTime($date->format('Y-m-d 00:00'));
    }

    public static function nbMonthInterval(DateTime $dateStart, DateTime $dateFin)
    {
        $diff = $dateStart->diff($dateFin);
        $mDiff = $diff->y * 12 + $diff->m;
        if ($diff->d > 0) {
            $mDiff++;
        }
        return $mDiff;
    }

    public static function dateEnToFr(DateTime $date, $format): string
    {
        $str = $date->format($format);

        foreach (self::$monthsEnToFr as $en=>$fr) {
            $str = str_ireplace($en, $fr, $str);
        }
        foreach (self::$daysEnToFr as $en=>$fr) {
            $str = str_ireplace($en, $fr, $str);
        }

        return $str;
    }

    public static function now() : DateTime
    {
        return new DateTime('now');
    }


}
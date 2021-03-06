<?php

// Start of calendar v.7.1.1

/**
 * Converts Julian Day Count to Gregorian date
 * @link http://www.php.net/manual/en/function.jdtogregorian.php
 * @param int $julianday A julian day number as integer
 * @return string The gregorian date as a string in the form "month/day/year"
 */
function jdtogregorian (int $julianday) {}

/**
 * Converts a Gregorian date to Julian Day Count
 * @link http://www.php.net/manual/en/function.gregoriantojd.php
 * @param int $month The month as a number from 1 (for January) to 12 (for December)
 * @param int $day The day as a number from 1 to 31
 * @param int $year The year as a number between -4714 and 9999
 * @return int The julian day for the given gregorian date as an integer.
 */
function gregoriantojd (int $month, int $day, int $year) {}

/**
 * Converts a Julian Day Count to a Julian Calendar Date
 * @link http://www.php.net/manual/en/function.jdtojulian.php
 * @param int $julianday A julian day number as integer
 * @return string The julian date as a string in the form "month/day/year"
 */
function jdtojulian (int $julianday) {}

/**
 * Converts a Julian Calendar date to Julian Day Count
 * @link http://www.php.net/manual/en/function.juliantojd.php
 * @param int $month The month as a number from 1 (for January) to 12 (for December)
 * @param int $day The day as a number from 1 to 31
 * @param int $year The year as a number between -4713 and 9999
 * @return int The julian day for the given julian date as an integer.
 */
function juliantojd (int $month, int $day, int $year) {}

/**
 * Converts a Julian day count to a Jewish calendar date
 * @link http://www.php.net/manual/en/function.jdtojewish.php
 * @param int $juliandaycount 
 * @param bool $hebrew [optional] If the hebrew parameter is set to true, the
 * fl parameter is used for Hebrew, string based,
 * output format.
 * @param int $fl [optional] The available formats are: 
 * CAL_JEWISH_ADD_ALAFIM_GERESH,
 * CAL_JEWISH_ADD_ALAFIM,
 * CAL_JEWISH_ADD_GERESHAYIM.
 * @return string The jewish date as a string in the form "month/day/year"
 */
function jdtojewish (int $juliandaycount, bool $hebrew = null, int $fl = null) {}

/**
 * Converts a date in the Jewish Calendar to Julian Day Count
 * @link http://www.php.net/manual/en/function.jewishtojd.php
 * @param int $month The month as a number from 1 to 13,
 * where 1 means Tishri,
 * 13 means Elul, and
 * 6 and 7 mean
 * Adar in regular years, but Adar I
 * and Adar II, respectively, in leap years.
 * @param int $day The day as a number from 1 to 30.
 * If the month has only 29 days, the first day of the following month is
 * assumed.
 * @param int $year The year as a number between 1 and 9999
 * @return int The julian day for the given jewish date as an integer.
 */
function jewishtojd (int $month, int $day, int $year) {}

/**
 * Converts a Julian Day Count to the French Republican Calendar
 * @link http://www.php.net/manual/en/function.jdtofrench.php
 * @param int $juliandaycount 
 * @return string The french revolution date as a string in the form "month/day/year"
 */
function jdtofrench (int $juliandaycount) {}

/**
 * Converts a date from the French Republican Calendar to a Julian Day Count
 * @link http://www.php.net/manual/en/function.frenchtojd.php
 * @param int $month The month as a number from 1 (for Vend??miaire) to 13 (for the period of 5-6 days at the end of each year)
 * @param int $day The day as a number from 1 to 30
 * @param int $year The year as a number between 1 and 14
 * @return int The julian day for the given french revolution date as an integer.
 */
function frenchtojd (int $month, int $day, int $year) {}

/**
 * Returns the day of the week
 * @link http://www.php.net/manual/en/function.jddayofweek.php
 * @param int $julianday A julian day number as integer
 * @param int $mode [optional] <table>
 * Calendar week modes
 * <table>
 * <tr valign="top">
 * <td>Mode</td>
 * <td>Meaning</td>
 * </tr>
 * <tr valign="top">
 * <td>0 (Default)</td> 
 * <td>
 * Return the day number as an int (0=Sunday, 1=Monday, etc)
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>1</td> 
 * <td>
 * Returns string containing the day of week
 * (English-Gregorian)
 * </td>
 * </tr>
 * <tr valign="top">
 * <td>2</td> 
 * <td>
 * Return a string containing the abbreviated day of week
 * (English-Gregorian)
 * </td>
 * </tr>
 * </table>
 * </table>
 * @return mixed The gregorian weekday as either an integer or string.
 */
function jddayofweek (int $julianday, int $mode = null) {}

/**
 * Returns a month name
 * @link http://www.php.net/manual/en/function.jdmonthname.php
 * @param int $julianday 
 * @param int $mode 
 * @return string The month name for the given Julian Day and calendar.
 */
function jdmonthname (int $julianday, int $mode) {}

/**
 * Get Unix timestamp for midnight on Easter of a given year
 * @link http://www.php.net/manual/en/function.easter-date.php
 * @param int $year [optional] The year as a number between 1970 an 2037. If omitted, defaults to the
 * current year according to the local time.
 * @return int The easter date as a unix timestamp.
 */
function easter_date (int $year = null) {}

/**
 * Get number of days after March 21 on which Easter falls for a given year
 * @link http://www.php.net/manual/en/function.easter-days.php
 * @param int $year [optional] The year as a positive number. If omitted, defaults to the
 * current year according to the local time.
 * @param int $method [optional] Allows Easter dates to be calculated based
 * on the Gregorian calendar during the years 1582 - 1752 when set to
 * CAL_EASTER_ROMAN. See the calendar constants for more valid
 * constants.
 * @return int The number of days after March 21st that the Easter Sunday
 * is in the given year.
 */
function easter_days (int $year = null, int $method = null) {}

/**
 * Convert Unix timestamp to Julian Day
 * @link http://www.php.net/manual/en/function.unixtojd.php
 * @param int $timestamp [optional] A unix timestamp to convert.
 * @return int A julian day number as integer.
 */
function unixtojd (int $timestamp = null) {}

/**
 * Convert Julian Day to Unix timestamp
 * @link http://www.php.net/manual/en/function.jdtounix.php
 * @param int $jday A julian day number between 2440588 and 2465342.
 * @return int The unix timestamp for the start (midnight, not noon) of the given Julian day.
 */
function jdtounix (int $jday) {}

/**
 * Converts from a supported calendar to Julian Day Count
 * @link http://www.php.net/manual/en/function.cal-to-jd.php
 * @param int $calendar Calendar to convert from, one of 
 * CAL_GREGORIAN,
 * CAL_JULIAN,
 * CAL_JEWISH or
 * CAL_FRENCH.
 * @param int $month The month as a number, the valid range depends 
 * on the calendar
 * @param int $day The day as a number, the valid range depends 
 * on the calendar
 * @param int $year The year as a number, the valid range depends 
 * on the calendar
 * @return int A Julian Day number.
 */
function cal_to_jd (int $calendar, int $month, int $day, int $year) {}

/**
 * Converts from Julian Day Count to a supported calendar
 * @link http://www.php.net/manual/en/function.cal-from-jd.php
 * @param int $jd Julian day as integer
 * @param int $calendar Calendar to convert to
 * @return array an array containing calendar information like month, day, year,
 * day of week, abbreviated and full names of weekday and month and the
 * date in string form "month/day/year".
 */
function cal_from_jd (int $jd, int $calendar) {}

/**
 * Return the number of days in a month for a given year and calendar
 * @link http://www.php.net/manual/en/function.cal-days-in-month.php
 * @param int $calendar Calendar to use for calculation
 * @param int $month Month in the selected calendar
 * @param int $year Year in the selected calendar
 * @return int The length in days of the selected month in the given calendar
 */
function cal_days_in_month (int $calendar, int $month, int $year) {}

/**
 * Returns information about a particular calendar
 * @link http://www.php.net/manual/en/function.cal-info.php
 * @param int $calendar [optional] Calendar to return information for. If no calendar is specified
 * information about all calendars is returned.
 * @return array 
 */
function cal_info (int $calendar = null) {}


/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_GREGORIAN', 0);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_JULIAN', 1);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_JEWISH', 2);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_FRENCH', 3);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_NUM_CALS', 4);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_DOW_DAYNO', 0);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_DOW_SHORT', 2);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_DOW_LONG', 1);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_GREGORIAN_SHORT', 0);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_GREGORIAN_LONG', 1);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_JULIAN_SHORT', 2);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_JULIAN_LONG', 3);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_JEWISH', 4);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_MONTH_FRENCH', 5);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_EASTER_DEFAULT', 0);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_EASTER_ROMAN', 1);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_EASTER_ALWAYS_GREGORIAN', 2);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_EASTER_ALWAYS_JULIAN', 3);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_JEWISH_ADD_ALAFIM_GERESH', 2);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_JEWISH_ADD_ALAFIM', 4);

/**
 * 
 * @link http://www.php.net/manual/en/calendar.constants.php
 */
define ('CAL_JEWISH_ADD_GERESHAYIM', 8);

// End of calendar v.7.1.1

<?php

namespace BlueBillywig\VMSRPC;

use finfo;

/**
 * Class VMSUtil
 * @package BlueBillywig\VMSRPC
 */
class VMSUtil
{
    /**
     * @param $message
     * @param string $cat
     * @param int $level
     * @param string $logdir
     */
    public static function debugmsg($message,$cat="default",$level=1,$logdir='')
    {
        if (empty($logdir)) {
            $logdir = dirname(__FILE__) . '/logs';
        }
        if (0 >= $level) {
            $currdate=date(DATE_RFC822);
            $logfile=$logdir . '/' .$cat . '.log';
            $fh = fopen($logfile, 'a');

            if ($fh !== false) {
                fwrite($fh, "$currdate\t $message");
                fwrite($fh, "\n");
                fclose($fh);
            }
        }
    }

    /**
     * @param $filename
     * @param string $default
     * @return string
     */
    public static function file_extension($filename,$default="")
    {
        $path_info = pathinfo($filename);
        $ext=$path_info['extension'];
        if (strlen($ext) < 5) {
            if (strlen($ext) >0) {
                return $ext;
            }
        }
        else {
            return $default;
        }

    }

    /**
     * @param $extension
     * @param $path
     * @return array|bool
     */
    public static function filelist_by_ext($extension, $path)
    {
        $list = array(); //initialise a variable
        $dir_handle = @opendir($path) or self::debugmsg("unable to opendir $path"); //attempt to open path
        while($file = readdir($dir_handle)){ //loop through all the files in the path
            if($file == "." || $file == ".."){continue;} //ignore these
            $filename = explode(".",$file); //seperate filename from extenstion
            $cnt = count($filename); $cnt--; $ext = $filename[$cnt]; //as above
            if(strtolower($ext) == strtolower($extension)){ //if the extension of the file matches the extension we are looking for...
                array_push($list, $file); //...then stick it onto the end of the list array
            }
        }
        if(is_array($list)){ //...if matches were found...
            return $list; //...return the array
        } else {//otherwise...
            return false;
        }
    }

    /**
     * @param $in
     * @return bool
     */
    public static function is_date($in)
    {
        return (boolean) strtotime($in);
    }

    /**
     * @return false|string
     */
    public static function xmlDate()
    {
        $xmlDate=date("Y-m-d");
        return $xmlDate;
    }

    /**
     * @param string $date
     * @return bool|false|string
     */
    public static function solrDate($date="now")
    {
        if($date == '') {
            return false;
        }
        $stamp=strtotime($date);
        return self::timeToUTC($stamp);
    }

    /**
     * @param string $date
     * @return bool|false|string
     */
    public static function rfc822Date($date="now")
    {
        if ($date == '') {
            return false;
        }
        if ($date == "now") {
            $xmlDate=date("r");
        }
        else {
            $timestamp=strtotime($date);
            $xmlDate=date("r",$timestamp);
        }
        return $xmlDate;
    }

    /**
     * @param string $date
     * @return bool|false|string
     */
    public static function iso8601Date($date="now")
    {
        if($date == '') {
            return false;
        }
        if ($date == "now") {
            $xmlDate=date("c");
        }
        else {
            $timestamp=strtotime($date);
            $xmlDate=date("c",$timestamp);
        }
        return $xmlDate;
    }

    /**
     * @param string $date
     * @return bool|string
     */
    public static function unixDate($date="now")
    {
        if($date == '') {
            return false;
        }
        if ($date == "now") {
            $xmlDate=date("U");
        }
        else {
            $timestamp=strtotime($date);
            $xmlDate=date("U",$timestamp);
        }
        return $xmlDate . '000';

    }

    /**
     * @param $strURL
     * @return bool
     */
    public static function url_exists($strURL)
    {
        $cp = curl_init();
        curl_setopt($cp, CURLOPT_URL, $strURL);
        curl_setopt($cp, CURLOPT_FAILONERROR, 1);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_setopt($cp, CURLOPT_RETURNTRANSFER, 1);

        curl_exec ($cp);

        $intReturnCode = curl_getinfo($cp, CURLINFO_HTTP_CODE);
        curl_close ($cp);

        if ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) {
            return false;
        }
        else {
            return true ;
        }
    }

    /**
     * @param $file
     * @return bool|string
     */
    public static function file_mimetype($file)
    {
        if (class_exists('finfo')){
            $handle = new finfo(FILEINFO_MIME);
            $mime_type = $handle->file($file);

            // overrule for wmv files
            if (preg_match("/\.wmv$/",$file)) {
                $mime_type="video/x-ms-asf";
            }

            return $mime_type;
        }
        else {
            return false;
        }
    }

    /**
     * @param $seconds
     * @return string
     */
    public static function sec_to_time($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }

    /**
     * @param $str
     * @return bool
     */
    public static function is_utf8($str)
    {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for($i=0; $i<$len; $i++){
            $c=ord($str[$i]);
            if($c > 128){
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1){
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * @param $url
     * @param string $arBasicAuth
     * @param $arPost
     * @return mixed
     */
    public static function curlFetch($url,$arBasicAuth='',$arPost)
    {
        // fetches url and returns response as string
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        if(is_array($arBasicAuth)) {
            $user=$arBasicAuth['account'];
            $pass=$arBasicAuth['password'];
            self::debugmsg("try to login with user $user and pass $pass to fetch url $url","curlfetch");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
        }
        if(! empty($arPost)) {
            curl_setopt ( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Expect:') );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $arPost ); // post the xml
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @param $passeddt
     * @return false|string
     */
    public static function timeToUTC($passeddt)
    {
        // Get the default timezone
        $default_tz = date_default_timezone_get();

        // Set timezone to UTC
        date_default_timezone_set("UTC");

        // convert datetime into UTC
        $utc_format = date("Y-m-d\TG:i:s\Z", $passeddt);

        // Might not need to set back to the default but did just in case
        date_default_timezone_set($default_tz);

        return $utc_format;
    }
}

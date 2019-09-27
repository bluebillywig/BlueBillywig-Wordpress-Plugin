<?php

namespace BlueBillywig\VMSRPC;

use Exception;
use DOMDocument;

/**
 * Class RPC
 *
 * v0.97 Jul 2018
 *
 * @package BlueBillywig\VMSRPC
 */
class RPC
{

    public $host, $user, $password,$userData;
    public $loggedIn,$curl_handle,$sharedSecret;
    protected $useragent;

    /**
     * RPC constructor.
     * @param $host
     * @param null $user
     * @param null $password
     * @param null $sharedSecret
     * @throws Exception
     */
    function __construct($host,$user=null,$password=null,$sharedSecret=null,$useragent='VMS RPC 0.97')
    {
        $publicMode=false;
        //VMSUtil::debugmsg("called new vmsrpc with host $host user $user and password $password","vmsrpc");
        if (  (! isset($user)) && (! isset($sharedSecret) )) {
            //VMSUtil::debugmsg("no user or shared secret specified, will switch to public mode","vmsrpc");
            $publicMode=true;
        }

        if(! isset($host)) {
            throw new Exception("No VMS host specified");
        }
        $this->host=$host;
        // initiate curl
        $this->curl_handle=curl_init();

        $this->initializeCurl();

        if(! $publicMode) {
            if(isset($user)) {
                // we will use a session
                $this->hasSession=true;
                curl_setopt($this->curl_handle,CURLOPT_COOKIESESSION,1);
                curl_setopt($this->curl_handle,CURLOPT_COOKIEFILE,'');
                $this->user=$user;
                $this->password=$password;
                //$this->createSession();
                $this->login();
            }
            elseif(isset($sharedSecret)) {
                // store sharedSecret so it can be used to generate a one time password on each request
                $this->sharedSecret=$sharedSecret;
            }
        }

        $this->useragent = $useragent;
    }

    /**
     * RPC destructor.
     */
    function __destruct()
    {
        if(isset($this->curl_handle)) {
            VMSUtil::debugmsg("destroying vms client object","vmsrpc");
            curl_close($this->curl_handle);
        }
    }

    /**
     * @param $entity
     * @param $action
     * @param $arProps
     * @return array|bool
     * @throws Exception
     */
    public function doAction($entity,$action,$arProps, $useSAPI = false)
    {
        VMSUtil::debugmsg("doAction called with entity $entity and action $action","vmsrpc");

        if($useSAPI){
            $uri='/sapi/' . $entity . '?action=' . $action;
        }else{
            $uri='/api/' . $entity . '?action=' . $action;
        }
        $response=$this->processResponse($this->fetch($uri,$arProps));
        return $response;
    }

    /**
     * @param $entity
     * @param null $objectId
     * @param null $arProps
     * @return bool|mixed
     * @throws Exception
     */
    public function xml($entity,$objectId=null,$arProps=null)
    {
        VMSUtil::debugmsg("xml called with entity $entity and objectId $objectId","vmsrpc");
        if(isset($objectId) && is_numeric($objectId)) {
            $uri='/api/' . $entity . '?action=get' . '&id=' . $objectId;
        }
        else {
            $uri='/sapi/' . $entity;
        }
        if(! isset($arProps['action'])) {
            // default action is get for this method
            $arProps['action']="get";
        }

        $response=$this->fetch($uri,$arProps);
        return $response;
    }

    /**
     * @param $entity
     * @param null $objectId
     * @param null $arProps
     * @return bool|mixed
     * @throws Exception
     */
    public function json($entity,$objectId=null,$arProps=null)
    {
        VMSUtil::debugmsg("json called with entity $entity and objectId $objectId","vmsrpc");
        if(isset($objectId) && is_numeric($objectId)) {
            $uri='/json/' . $entity . '/' . $objectId;
        }
        else {
            $uri='/json/' . $entity;
        }
        VMSUtil::debugmsg("will call fetch","vmsrpc");
        $response=$this->fetch($uri,$arProps);
        return $response;
    }

    /**
     * @param $entity
     * @param null $objectId
     * @param string $action
     * @param null $arProps
     * @param null $entityAction
     * @param null $urlParameters
     * @return bool|mixed
     * @throws Exception
     */
    public function sapi($entity,$objectId=null, $action = "GET", $arProps=array(), $entityAction = null, $urlParameters = null)
    {
        VMSUtil::debugmsg("json called with entity $entity and objectId $objectId","vmsrpc");
        if(isset($objectId) && is_numeric($objectId)) {
            $uri='/sapi/' . $entity . '/' . $objectId . ($entityAction ? '/' . $entityAction  : '');
        }
        else {
            $uri='/sapi/' . $entity;
        }
        if ($urlParameters !== null) {
            $uri.= '?';
            foreach($urlParameters as $key => $value) {
                $uri.= '&'.rawurlencode($key).'='.rawurlencode($value);
            }
        }

        if(is_array($arProps) && isset($arProps))
            VMSUtil::debugmsg("will call fetch with uri $uri and properties: " . implode('\r\n', $arProps),"vmsrpc");
        else
            VMSUtil::debugmsg("will call fetch with uri $uri and properties: " . $arProps, "vmsrpc");
        $response=$this->fetch($uri,$arProps, $action);
        return $response;
    }

    /**
     * @param $apiEntityUrl
     * @param null $qs
     * @param null $arProps
     * @return bool|mixed
     * @throws Exception
     */
    public function uri($apiEntityUrl, $qs=null,$arProps=null)
    {
        VMSUtil::debugmsg("uri called with entity $apiEntityUrl and queryString $qs","vmsrpc");

        $uri= '/'.$apiEntityUrl.(strstr($apiEntityUrl,'.') != '' ? '' : '/') . (strstr($qs, '=') != '' ? '?' : '') . $qs;

        if(!empty($arProps)) {
            foreach($arProps as $k => $v) {
                $uri .= '&'.$k."=".$v;
            }
        }
        VMSUtil::debugmsg("will call fetch","vmsrpc");
        $response=$this->fetch($uri, NULL);
        return $response;
    }

    public function curi($path)
    {
        if(strlen($path) > 0 && substr($path, 0, 1) !== '/'){
            $path = '/' . $path;
        }
        VMSUtil::debugmsg("Making call on path: $path","vmsrpc");
        $response=$this->fetch($path, NULL);
        return $response;
    }

    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function calculateRequestToken()
    {
        // strip off the id
        $arToken=preg_split('/-/',$this->sharedSecret);
        $tokenId=$arToken[0];
        $result=HOTP::generateByTime($arToken[1],120,time());
        return $tokenId .'-' .  $result->toString();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function login()
    {
        // try to login to VMS and build a valid session
        $xmlRandom=$this->fetch('/api/getRandom');
        //VMSUtil::debugmsg("api/random fetched: $xmlRandom","vmsrpc");
        if(! $response=$this->processResponse($xmlRandom)) {
            VMSUtil::debugmsg("invalid xml for getRandom","xmlrpc");
            throw new Exception('Login exception');
        }
        else {
            $random=$response['body'];
            //VMSUtil::debugmsg("fetched auth random: $random","vmsrpc");
        }

        $crypt=$this->calculatePassword($random);
        //VMSUtil::debugmsg("crypt: $crypt","vmsrpc");

        $loginUri="/api/bbauth?action=get_user&username=" . urlencode( $this->user ) . "&password=" . urlencode($crypt);
        $loginResponseXml=$this->fetch($loginUri);
        $this->userData=$loginResponseXml;
        $loginResponse=$this->processResponse($loginResponseXml);
        $strR=print_r($loginResponse,true);
        VMSUtil::debugmsg("login response was $strR","vmsrpc");
        if (($loginResponse['error'] == "false") || ($loginResponse['error'] == '')) {
            VMSUtil::debugmsg("logged in to VMS","vmsrpc");
            $this->loggedIn=true;
            return true;
        }
        else {
            throw new Exception("login failed, wrong credentials?");
        }
    }

    /**
     * @param $random
     * @return string
     */
    protected function calculatePassword($random)
    {
        //VMSUtil::debugmsg("taking base64 encoded md5sum of $this->password + $random","vmsrpc");
        $base=base64_encode(md5($this->password));
        $base=$base . $random;
        return base64_encode(md5($base));
    }

    /**
     * @param $uri
     * @param null $arPostFields
     * @param null $action
     * @return bool|mixed
     * @throws Exception
     */
    protected function fetch($uri,$arPostFields=null,$action=null)
    {
        if (! isset($this->host)) {
            return false;
        }

        // check for file references. If not readable throw exception
        $rawPost=false;
        if(is_array($arPostFields)) {
            foreach($arPostFields as $key=>$value) {
                if(is_string($value) && preg_match('/^@/',$value)) {
                    VMSUtil::debugmsg("found a file reference: $value","vmsrpc");
                    $filetoupload=preg_replace('/^@/','',$value);
                    if(is_readable($filetoupload) && filesize($filetoupload) >0 ) {
                        VMSUtil::debugmsg("will upload file: $filetoupload","vmsrpc");
                    }
                    else {
                        throw new Exception("File $filetoupload does not exist, is empty or is not readable");

                    }
                }
            }

        }
        else{  // raw post body
            $rawPost=true;

        }



        if(! empty($this->sharedSecret)){
            curl_reset( $this->curl_handle );
            $this->initializeCurl();

            $onetimeToken=$this->calculateRequestToken();
            if($rawPost){
                if(stripos($uri, '?') === false){
                    $uri .= "?";
                }
                $uri .= '&rpctoken=' . $onetimeToken;
            }
            else{
                $arPostFields['rpctoken']=$onetimeToken;
            }
        }
        if(!empty($action) && in_array($action, array("GET", "POST", "DELETE"))) {
            curl_setopt($this->curl_handle, CURLOPT_CUSTOMREQUEST, $action);
        }
        curl_setopt($this->curl_handle, CURLOPT_URL, "$this->host" . $uri);
        VMSUtil::debugmsg("will fetch $this->host" . $uri,'vmsrpc');

        if(!empty($arPostFields) ) {
            VMSUtil::debugmsg("arPostFields is : " . print_r($arPostFields,true),'vmsrpc');
            if(strstr($uri, 'sapi') != '' && ! $rawPost) {
                $arPostFields = json_encode($arPostFields);
            }
            curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS,$arPostFields);
        }

        $result = curl_exec ($this->curl_handle);
        VMSUtil::debugmsg("uri: $uri delivers result: $result","vmsrpc");
        return $result;
    }

    /**
     * @param $rawResponse
     * @return bool|array
     * @throws Exception
     */
    protected function processResponse($rawResponse)
    {
        if(! isset($rawResponse) || empty($rawResponse)) {
            throw new Exception('Empty response');
        }

        $dom = new DOMDocument();
        if (! $dom->loadXML($rawResponse)) {
            return false;
        }
        $root=$dom->documentElement;
        $response['code']=$root->getAttribute("code");
        $response['error']=$root->getAttribute("error");
        $response['body']=$root->textContent;
        $response['id']=$root->getAttribute("id");
        return $response;
    }

    /**
     * @return void
     */
    private function initializeCurl()
    {
        curl_setopt($this->curl_handle, CURLOPT_TIMEOUT, 600);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT,$this->useragent);
    }
}
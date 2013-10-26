<?php
/**
 * Xero.php
 *
 * A Xero API authentication and usage library.
 *
 * Constructed from code by Chris Santala & Ronan Quirke
 * @link https://github.com/XeroAPI/XeroOAuth-PHP
 *
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *

 */

class Xero extends CApplicationComponent
{
//TODO add methods

    const APP_TYPE_PRIVATE = 'private';
    const APP_TYPE_PUBLIC = 'public';
    const APP_TYPE_PARTNER = 'partner';



    /**
     * @var string The application type, public, private or partner
     *
     */
    public $appType;

    /**
     * @var string the OAuthCallback address
     */
    public $oAuthCallback;

    /**
     * @var boolean current connection status
     */
    private $_isConnected = false;



    /**
     * @var array
     * Array of oauth signatures
     */
    public $signatures = array();

    /**
     * array of xero app-type specific settings
     * @var array
     */
    public $xroSettings = array();

    /**
     * Default settings for private apps
     * @var array
     */
    protected $xroPrivateDefaults = array(
        'xero_url' => 'https://api.xero.com/api.xro/2.0',
        'site' => 'https://api.xero.com',
        'authorize_url' => 'https://api.xero.com/oauth/Authorize',
        'signature_method' => 'RSA-SHA1'
    );


    /**
     * Standard Xero OAuth Options
     * @var array
     */
    protected $xroConsumerOptions = array(
        'request_token_path' => 'https://api.xero.com/oauth/RequestToken',
        'access_token_path' => 'https://api.xero.com/oauth/AccessToken',
        'authorize_path' => 'https://api.xero.com/oauth/Authorize'
    );

    /**
     * Makes a GET call to the Xero API
     * @param $end_point
     * @param null $id
     * @return mixed
     * @throws CHttpException on authentication problems
     */
    public function apiGet($end_point, $id = NULL) {

        if (!$this->_isConnected)  //connect if not already connected
            $this->connect();

        # Set some standard curl options....
        $options = $this->setCurlOptions();

        $oauthObject = new OAuthSimple();

        $this->signatures['oauth_token'] = Yii::app()->session['access_token'];
        $this->signatures['oauth_secret'] = Yii::app()->session['access_token_secret'];

        if ($this->appType =! "Public") {
            $this->signatures['oauth_session_handle'] = Yii::app()->session['oauth_session_handle'];
        }
        else $this->signatures['oauth_session_handle'] = NULL;
        //////////////////////////////////////////////////////////////////////

        // Xero API Access:
        $oauthObject->reset();


                $result = $oauthObject->sign(array(
                'path' => $this->xroSettings['xero_url'].'/'.$end_point.'/'.$id,
                'parameters' => array(
                    //   'order' => urlencode(),
                    'oauth_signature_method' => $this->xroSettings['signature_method']),
                'signatures'=> $this->signatures
            ));
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
        $r = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);


        if ($this->checkReturnedItems($r, $http_status))
            return $r; // all good return results from API
        else
            return false;
    }


    /**
     * Makes a POST request to the Xero API
     * @param string $endPoint
     * @param string $xmlData
     * @param string $id
     * @return mixed the string if complete, false otherwise
     * @throws CHttpException if invalid endpoint
     * TODO add error checking, checking for 200 return etc.
     */
    public function apiPost($endPoint, $xmlData, $id=NULL)
    {
        if (!in_array($endPoint, array('Contacts', 'Invoices')))//check endpoint is allowed
            throw new CHttpException(500, Yii::t('yii-xero', 'Invalid Endpoint {endpoint}', array('{endpoint}'=> $endPoint)));

        if (!$this->_isConnected)
            $this->connect();

        # Set some standard curl options....
        $options = $this->setCurlOptions();

        $oauthObject = new OAuthSimple();

        $this->signatures['oauth_token'] = Yii::app()->session['access_token'];
        $this->signatures['oauth_secret'] = Yii::app()->session['access_token_secret'];
        if ($this->appType =! "Public") {
            $this->signatures['oauth_session_handle'] = Yii::app()->session['oauth_session_handle'];
        }
        else $this->signatures['oauth_session_handle'] = NULL;
        //////////////////////////////////////////////////////////////////////

        $oauthObject->reset();
        $result = $oauthObject->sign(array(
            'path' => $this->xroSettings['xero_url'].'/'.$endPoint.'/'.$id,
            'action' => 'POST',
            'parameters'=> array(
                'oauth_signature_method' => $this->xroSettings['signature_method'],
                'xml' => $xmlData),
            'signatures'=> $this->signatures));

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_POST, true);
        $post_body = urlencode($xmlData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "xml=" . $post_body);

        $url = $result['signed_url'];
        curl_setopt($ch, CURLOPT_URL, $url);
        $r = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($this->checkReturnedItems($r, $http_status))
            return $r; // on success, return the XML from the post
        else
            return null;
    }



    /**
     * Connects to Xero
     */
    public function connect()
    {
        return $this->oauth();
    }


    /**
     * @return bool current connection status
     */
    public function getIsConnected()
    {
        return $this->_isConnected;
    }


    /**
     * Sets up Xero Application-specific settings
     * @throws CException invalid xero app type
     */
    public function init()
    {
        switch($this->appType)
        {
            case self::APP_TYPE_PRIVATE :
                $this->xroSettings = $this->xroPrivateDefaults;
                //todo change this, it's hacky
                $_GET['oauth_verifier'] = 1;
                $_COOKIE['oauth_token_secret'] = $this->signatures['shared_secret'];
                $_GET['oauth_token'] =  $this->signatures['consumer_key'];
                break;
            default :
                throw new CException(Yii::t('xero', 'Invalid Xero Application Type'));
        }
    }


    /**
     * Removes the current session
     */
    public function disconnect()
    {
        if (!$this->_isConnected)
        {
            Yii::app()->session->destroy();
            $this->_isConnected = false;
        }
    }






    /**********************************************************************************************
     *
     *          Protected functions
     *
     *
     *******************************************************************************************/

    protected function oauth() {

        $oauthObject = new OAuthSimple();
        $output = 'Authorizing...';

        # Set some standard curl options....
        $options = $this->setCurlOptions();

        // In step 3, a verifier will be submitted.  If it's not there, we must be
        // just starting out. Let's do step 1 then.
        if (!isset($_GET['oauth_verifier'])) {
            ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
            // Step 1: Get a Request Token
            //
            // Get a temporary request token to facilitate the user authorization
            // in step 2. We make a request to the OAuthGetRequestToken endpoint,
            // submitting the scope of the access we need (in this case, all the
            // user's calendars) and also tell Google where to go once the token
            // authorization on their side is finished.
            //
            $result = $oauthObject->sign(array(
                'path' => $this->xroSettings['site'].$this->xroConsumerOptions['request_token_path'],
                'parameters' => array(
                    'scope' => $this->xroSettings['xero_url'],
                    'oauth_callback' => $this->oAuthCallback,
                    'oauth_signature_method' => $this->xroSettings['signature_method']),
                'signatures'=> $this->signatures));

            // The above object generates a simple URL that includes a signature, the
            // needed parameters, and the web page that will handle our request.  I now
            // "load" that web page into a string variable.
            $ch = curl_init();

            curl_setopt_array($ch, $options);

            Yii::trace ('signed_url: ' . $result['signed_url'] . '<br/>', 'yii-xero');

            curl_setopt($ch, CURLOPT_URL, $result['signed_url']);

            $r = curl_exec($ch);

            Yii::trace ('CURL ERROR: ' . curl_error($ch) . '<br/>', 'yii-xero');

            curl_close($ch);

            Yii::trace('CURL RESULT: ' . print_r($r, true) . '<br/>', 'yii-xero');


            // We parse the string for the request token and the matching token
            // secret. Again, I'm not handling any errors and just plough ahead
            // assuming everything is hunky dory.
            parse_str($r, $returned_items);
            $request_token = $returned_items['oauth_token'];
            $request_token_secret = $returned_items['oauth_token_secret'];

            Yii::trace('request_token: ' . $request_token . '<br/>', true);

            // We will need the request token and secret after the authorization.
            // Google will forward the request token, but not the secret.
            // Set a cookie, so the secret will be available once we return to this page.
//            setcookie("oauth_token_secret", $request_token_secret, time()+1800);  //non-yii method
            Yii::app()->request->cookies['oauth_token_secret'] = new CHttpCookie('oauth_token_secret', $request_token_secret, array('expire' => time()+1800));
            //
            //////////////////////////////////////////////////////////////////////

            ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
            // Step 2: Authorize the Request Token
            //
            // Generate a URL for an authorization request, then redirect to that URL
            // so the user can authorize our access request.  The user could also deny
            // the request, so don't forget to add something to handle that case.
            $result = $oauthObject->sign(array(
                'path' => $this->xroSettings['authorize_url'],
                'parameters' => array(
                    'oauth_token' => $request_token,
                    'oauth_signature_method' => $this->xroSettings['signature_method']),
                'signatures' => $this->signatures));

            // See you in a sec in step 3.
            if(YII_DEBUG){
                Yii::trace('signed_url: ' . $result['signed_url'], 'yii-xero');
            }else{
                header("Location:$result[signed_url]");
            }
            exit;
            //////////////////////////////////////////////////////////////////////
        }
        else {
            ///////////////////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
            // Step 3: Exchange the Authorized Request Token for an
            //         Access Token.
            //
            // We just returned from the user authorization process on Google's site.
            // The token returned is the same request token we got in step 1.  To
            // sign this exchange request, we also need the request token secret that
            // we baked into a cookie earlier.
            //

            // Fetch the cookie and amend our signature array with the request
            // token and secret.
            $this->signatures['oauth_secret'] = $_COOKIE['oauth_token_secret'];
            $this->signatures['oauth_token'] = $_GET['oauth_token'];

            // only need to do this for non-private apps
            if($this->appType !== self::APP_TYPE_PRIVATE) {
                // Build the request-URL...
                $result = $oauthObject->sign(array(
                    'path' => $this->xroSettings['site'].$this->xroConsumerOptions['access_token_path'],
                    'parameters' => array(
                        'oauth_verifier' => $_GET['oauth_verifier'],
                        'oauth_token' => $_GET['oauth_token'],
                        'oauth_signature_method' => $this->xroSettings['signature_method']),
                    'signatures'=> $this->signatures));

                // ... and grab the resulting string again.
                $ch = curl_init();
                curl_setopt_array($ch, $options);
                curl_setopt($ch, CURLOPT_URL, $result['signed_url']);
                $r = curl_exec($ch);

                // Voila, we've got an access token.
                parse_str($r, $returned_items);
                $access_token = $returned_items['oauth_token'];
                $access_token_secret = $returned_items['oauth_token_secret'];
                // $oauth_session_handle = $returned_items['oauth_session_handle'];
            }
            else {
                $access_token = $this->signatures['oauth_token'];
                $access_token_secret = $this->signatures['oauth_secret'];
            }

        }

        $this->signatures['oauth_token'] = $access_token;
        $this->signatures['oauth_secret'] = $access_token_secret;
        if ($this->appType =! self::APP_TYPE_PUBLIC) {
//            $this->signatures['oauth_session_handle'] = $oauth_session_handle;
        }
        //////////////////////////////////////////////////////////////////////

        Yii::app()->session['access_token'] = $access_token;
        Yii::app()->session['access_token_secret'] = $access_token_secret;
        Yii::app()->session['oauth_session_handle'] = $access_token_secret;
        $this->_isConnected = true;


        return $this->signatures;
    }

    /**
     * Checks if returned items contains an error
     * @param $r string returned items
     * @param $http_status int
     * @return bool
     * @throws CHttpException
     */
    protected function checkReturnedItems($r, $http_status)
    {

        switch ($http_status){

            case 200:
                return true;
                break;
            case 400:
                throw new CHttpException($http_status,$this->parseXmlError($r));
                break;
            case 401:
                throw new CHttpException($http_status,$this->parseOAuthError($r));
                break;
            case 403:
                throw new CHttpException($http_status,Yii::t('yii-xero', 'The client SSL certificate was not valid.'));
                break;
            case 404:
                throw new CHttpException($http_status,Yii::t('yii-xero', 'The resource you have specified cannot be found.'));
                break;
            case 500:
                throw new CHttpException($http_status,Yii::t('yii-xero', 'An unhandled Xero API error has occurred. Contact Xero API support if this persists.'));
                break;
            case 501:
                throw new CHttpException($http_status,Yii::t('yii-xero', 'The method you have called has not been implemented.'));
                break;
            case 503:
                $error = $this->parseOAuthError($r);  //is it rate limit exceeded, or API unavailable?
                if (!$error)
                    throw new CHttpException($http_status, Yii::t('yii-xero', 'The Xero API is currently unavailable. Try again later.'));
                else
                    throw new CHttpException($http_status, $error);
                break;
            default:
                throw new CHttpException(500, Yii::t('yii-xero', 'Unrecognised HTTP Response Code {code}', array('{code}'=> $http_status)));

        }

    }


    /**
     * Parses an OAuth get string into a meaningful error.
     * @param $r
     * @return mixed
     */
    protected function parseOAuthError($r)
    {
        parse_str($r, $returned_items);
        if(isset($returned_items['oauth_problem']))
            return Yii::t('yii-xero', 'OAuth Error: {error} - {advice}', array('{error}'=>$returned_items['oauth_problem'], '{advice}'=> $returned_items['oauth_problem_advice']));
        else
            return false;
    }


    /**
     * Parses returned xml to get an error message
     * @param $r string Xero Xml Error
     * @return string
     */
    protected function parseXmlError($r)
    {
        $xmlObject = new SimpleXMLElement($r);
        return (string)$xmlObject->Elements[0]->DataContractBase->ValidationErrors[0]->ValidationError->Message;

    /*
     * Xero Error Format:
     *
            <ApiException>
      <ErrorNumber>10</ErrorNumber>
      <Type>ValidationException</Type>
      <Message>A validation exception occurred</Message>
      <Elements>
        <DataContractBase xsi:type="Invoice">
          <ValidationErrors>
            <ValidationError>
              <Message>Email address must be valid.</Message>
            </ValidationError>
          </ValidationErrors>
       </DataContractBase>
      </Elements>
    </ApiException>
    */
    }


    /**
     * Parses returned xml to get Xero Item ID
     * @param $r
     * @return string
     */
    protected function parseXmlId($r)
    {
        $xmlObject = new SimpleXMLElement($r);

        var_dump($xmlObject);die;
        return ((string)$xmlObject->Id);
    }

    /**
     * Sets Curl Options for Xero Connection
     * @return array
     */
    protected function setCurlOptions() {

        if ($this->appType === self::APP_TYPE_PARTNER) {
            $options[CURLOPT_SSLCERT] = '/[path]/entrust-cert.pem';
            $options[CURLOPT_SSLKEYPASSWD] = '[password]';
            $options[CURLOPT_SSLKEY] = '/[path]/entrust-private.pem';
        }
        $options[CURLOPT_VERBOSE] = 1;
        $options[CURLOPT_RETURNTRANSFER] = 1;
        $options[CURLOPT_SSL_VERIFYHOST] = 0;
        $options[CURLOPT_SSL_VERIFYPEER] = 0;
        $useragent = (isset($useragent)) ? (empty($useragent) ? 'XeroOAuth-PHP' : $useragent) : 'XeroOAuth-PHP';
        $options[CURLOPT_USERAGENT] = $useragent;
        return $options;
    }



}



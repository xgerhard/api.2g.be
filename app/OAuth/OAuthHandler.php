<?php
namespace App\OAuth;

use Exception;
use Session;
use Redirect;
use App\OAuth\OAuthProvider;
use App\OAuth\OAuthSession;
use Carbon\Carbon;
use GuzzleHttp\Client;

class OAuthHandler
{
    public $provider;
    private $maxTokenLength = 31556952; // 1 year

    public function __construct($strService)
    {
        $this->setProvider($strService);
    }

    public function runAuth($request)
    {
        // If code is set, user authorized our app
        if($request->input('code') !== null)
        {
            // Validate state
            if(
                $request->input('state') === null ||
                !$request->session()->has('state') ||
                $request->input('state') != $request->session()->pull('state')
            )
            throw new Exception('Invalid state parameter');

            $oTokens = $this->getTokens($request->input('code'));
            if(isset($oTokens->error)) $this->handleError($oTokens->error);

            // Save tokens
            $OAuthSession = new OAuthSession;
            $OAuthSession->access_token = $oTokens->access_token;
            $OAuthSession->refresh_token = $oTokens->refresh_token;
            $OAuthSession->expires_in = Carbon::now()->addSeconds(isset($oTokens->expires_in) ? $oTokens->expires_in : $this->maxTokenLength);
            $OAuthSession->refresh_expires_in = Carbon::now()->addSeconds(isset($oTokens->refresh_expires_in) ? $oTokens->refresh_expires_in : $this->maxTokenLength);
            $OAuthSession->provider_id = $this->provider->id;

            $OAuthSession->save();
            $request->session()->put($this->provider->name .'-auth', $OAuthSession->id);

            Redirect::to($this->provider->local_redirect)->send();
            return $OAuthSession->access_token;
        }

        // If user denied authorization an error will be returned
        elseif($request->input('error') !== null)
            $this->handleError($request->input('error'));

        // Else it will be a new auth request
        else
            Redirect::to($this->getAuthUrl())->send();

        return false;
    }

    /*
    * getAuthUrl
    * Build the auth url and save state to session
    * return (string) auth url
    */
    public function getAuthUrl()
    {
        // Create and save state
        $strState = $this->generateState();
        Session::put('state', $strState);

        // Build url
        return $this->provider->auth_url 
            .'?response_type=code'
            .'&state='. $strState
            .'&client_id='. $this->provider->client_id 
            . (isset($this->provider->scope) ? '&scope='. $this->provider->scope : '')
            . (isset($this->provider->redirect_url) ? '&redirect_uri='. urlencode($this->provider->redirect_url) : '');
    }

    /*
    * generateState
    * Generate random string to validate user
    * return (string) random string
    */
    private function generateState()
    {
        return sha1(time() . rand(1, 9999));
    }

    /*
    * getTokens
    * Requests tokens to auth server
    * required (string) code / refresh token
    * optional (boolean) refresh, true for refresh token, false for code
    */
    public function getTokens($strCode, $bRefresh = false)
    {
        // Guzzle should be easy to use hmm
        $oClient = new Client([
            'http_errors' => false, 
            'verify' => false
        ]);

        $oResponse = $oClient->request('POST', $this->provider->token_url, [
            'form_params' => [
                'grant_type' => $bRefresh === false ? 'authorization_code' : 'refresh_token',
                $bRefresh === false ? 'code' : 'refresh_token' => $strCode,
                'client_id'    => $this->provider->client_id,
                'client_secret' => $this->provider->client_secret,
                'redirect_uri' => $this->provider->redirect_url
            ]
        ]);
        return json_decode($oResponse->getBody()->getContents());
    }

    /*
    * isAuthValid
    * Checks if Authsession is still valid
    * optional (int) OAuthSessionId
    * Optional (boolean) return boolean or OAuthSession object
    * return (boolean true or OAuthSession object) or false = invalid
    */
    public function isAuthValid($iAuthSessionId = null, $bReturn = false)
    {
        $OAuthSession = $iAuthSessionId ? OAuthSession::find($iAuthSessionId) : OAuthSession::where('provider_id', $this->provider->id)->orderBy('expires_in', 'DESC')->orderBy('id', 'DESC')->firstOrFail();
        if($OAuthSession)
        {
            // Access token still valid
            if($OAuthSession->expires_in > Carbon::now())
            {
               return $bReturn ? $OAuthSession : true;
            }

            // Access token expired, check if we can refresh it
            elseif($OAuthSession->refresh_expires_in > Carbon::now())
            {
                // refresh the token
                $this->setProvider($OAuthSession->provider_id, true);
                $oTokens = $this->getTokens($OAuthSession->refresh_token, true);
                if(isset($oTokens->error)) $this->handleError($oTokens->error);

                // Save new tokens
                $OAuthSession->access_token = $oTokens->access_token;
                $OAuthSession->refresh_token = $oTokens->refresh_token;
                $OAuthSession->expires_in = Carbon::now()->addSeconds(isset($oTokens->expires_in) ? $oTokens->expires_in : $this->maxTokenLength);
                $OAuthSession->refresh_expires_in = Carbon::now()->addSeconds(isset($oTokens->refresh_expires_in) ? $oTokens->refresh_expires_in : $this->maxTokenLength);
                $OAuthSession->save();
                return $bReturn ? $OAuthSession : true;
            }
        }
        return false;
    }

    /*
    * setProvider
    * Set the provider
    * required (int/string) Id or Name of provider
    * optional (boolean) if first parameter is an Id set this value true
    */
    public function setProvider($strService, $id = false)
    {
        $this->provider = $id === false ? OAuthProvider::where('name', $strService)->firstOrFail() :  OAuthProvider::findOrFail($strService);
    }

    private function handleError($strError)
    {       
        switch($strError)
        {
            case 'access_denied':
                $strError = 'Authorization was denied by client';
            break;
            
            case 'invalid_client':
                $strError = 'Something went wrong';
            break;

            case 'invalid_grant':
                $strError = 'Authorization code expired/invalid, please authorize again';
            break;
            
            default:
                $strError = 'Something went wrong';
        }
        throw new Exception($strError);
    }
}
?>

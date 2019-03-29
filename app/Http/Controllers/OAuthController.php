<?php
namespace App\Http\Controllers;

use App\OAuth\OAuthHandler;
use Illuminate\Http\Request;

class OAuthController
{
    public function OAuthHandler(Request $request, $strService)
    {
        try
        {
            $OAuthHandler = new OAuthHandler($strService);
            $OAuthHandler->runAuth($request);
        }
        catch (Exception $e)
        {
            return 'Error: '. $e->getMessage() .'.';
        }
    }
}
?>
<?php
namespace App\OAuth;

use Illuminate\Database\Eloquent\Model;

class OAuthProvider extends Model
{
    protected $fillable = [
        'name',
        'auth_url',
        'scope',
        'redirect_url',
        'local_redirect',
        'client_id',
        'client_secret',
        'token_url'
    ];
}
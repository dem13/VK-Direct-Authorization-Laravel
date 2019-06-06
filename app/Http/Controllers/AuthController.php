<?php

namespace App\Http\Controllers;

use App\User;
use App\Traits\GetsVkAccessToken;
use App\Traits\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use VK\Client\VKApiClient;

class AuthController extends Controller
{
	use AuthenticatesAndRegistersUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new Client();
    	$this->vk =  new VKApiClient();
        $this->middleware('guest')->except('logout');
    }
}

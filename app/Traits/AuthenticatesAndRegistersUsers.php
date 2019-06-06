<?php 

namespace App\Traits;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use GuzzleHttp\Exception\ClientException;


trait AuthenticatesAndRegistersUsers
{
    use RedirectsUsers, ThrottlesLogins;

    /**
     * VK API Client
     * 
     * @var VK\Client\VKApiClient
     */
    protected $vk;

    /**
     * HTTP client
     * 
     * @var GuzzleHttp\Client
     */
    protected $client;

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        try{
        	$this->attemptLogin($request);
        }catch(ClientException $e){
    		$response = json_decode($e->getResponse()->getBody()->getContents());

    		if(isset($response->captcha_sid)){
    			return $this->sendNeedCaptchaResponse($response->captcha_sid, $response->captcha_img);
    		}
    		
    		return $this->sendFailedLoginResponse($request);
    	}

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws GuzzleHttp\Exception\ClientException Throws If credentials are invalid
     */
    protected function attemptLogin(Request $request)
    {
    	$accessToken = $this->getAccessToken($request);

    	if(!$user = User::where('vk_id','=',$accessToken->user_id)->first()){
    		$user = $this->createUserUsingAccessToken($accessToken->access_token);
    	}

    	return $this->guard()->loginUsingId($user->id);
    }

    /**
     * Get VK access token(Authorize user)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return object VK user info(access token, user id...)
     *
     * @throws GuzzleHttp\Exception\ClientException Throws If credentials are invalid
     */
    protected function getAccessToken(Request $request)
    {
    	$params = [
    		'grant_type' => config('vk.grant_type'),
    		'client_id' => config('vk.client.id'),
    		'client_secret' => config('vk.client.secret'),
    		'username' => $request->username,
    		'password' => $request->password,
    		'scope' => config('vk.scope'),
    		'2fa_supported' => config('vk.2fa_supported'),
    		'v' => config('vk.v'),
    	];

    	if($request->has(['captcha_key', 'captcha_sid'])){
    		$params = array_merge($params, [
    			'captcha_sid' => $request->captcha_sid,
    			'captcha_key' => $request->captcha_key
    		]);
    	} 

    	$accessToken = $this->client->get(
    		config('vk.host') . config('vk.endpoint_access_token'),
    		['query' =>  $params]
    	)->getBody();

    	return json_decode($accessToken);
    }

    /**
     * Create new user using data provided by VK
     * 
     * @param  string $accessToken Token for taking user info from VK
     * @return App\User
     */
    protected function createUserUsingAccessToken($accessToken)
    {
    	$user = $this->vk->users()->get($accessToken)[0];

    	return User::create([
    		'first_name' => $user['first_name'],
    		'last_name' => $user['last_name'],
    		'vk_id' => $user['id'],
    		'access_token' => $accessToken,
    	]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $this->guard()->user())
                ?: redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        //
    }

    /**
     * Ask user to enter captcha
     * 
     * @param  string $captchaSid 
     * @param  string $captchaImg 
     * @return \Illuminate\Http\Response
     */
    protected function sendNeedCaptchaResponse($captchaSid, $captchaImg)
    {
    	return redirect()->back()->withErrors([
    		'captcha.need' => 'Captcha needed',
    		'captcha.sid' => $captchaSid,
    		'captcha.img' => $captchaImg
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }
}

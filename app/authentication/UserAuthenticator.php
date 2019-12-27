<?php

namespace App;

use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;
use Nette\Security\IIdentity;
use App\HttpMethods\HttpMethods;
use App\Helpers\HttpStatus;

/**
 *  Summary 
 *  UserAuthenticator is used for custom user authentication.
 * 	It implements IAuthenticator and expands its code to use Peachenka API.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @access public
 *  @since 38:login to account
 */
class UserAuthenticator implements IAuthenticator
{
	/** 
	 *  @var HttpMethods $httpMethods
	 */
	private $httpMethods;

	/** 
	 *  @var string $route 
	 */
	public $route = 'http://127.0.0.1:8000/api/v1';

	function __construct(HttpMethods $httpMethods)
	{
		$this->httpMethods = $httpMethods;
	}

	/**
	 *  Summary
	 *  authentication method.
	 * 
	 * 	@see loginFormSuccess in VerificationPresenter for login
	 * 
	 *  @param array $credentials - what is used for authentication (email, password)
	 *  @throws AuthenticationException if user isn't authenticated or some other error occures;
	 * 	@return IIdentity
	 */
    public function authenticate(array $credentials) : IIdentity
    {
		//credentials are passed in login
		[$email, $password] = $credentials;
		//array created with credentials and posted on /login route
		$login = [
			'email' => $email,
			'password' => $password
		];
		$data = $this->httpMethods->post($login, $this->route . '/login');
		//http code of response
		$httpCode = $data['info']['http_code'];
		//message from response
		$response = json_decode($data['response']);
		//if 401 throw exception with error message
		if ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			throw new AuthenticationException($response->error);
		//if any code accept for 401 and 200 returned, throw exception
		} elseif($httpCode !== HttpStatus::STATUS_OK) {
			throw new AuthenticationException($httpCode . ': Something went wrong.');
		}
		//if 200, get token from response
		$token = $response->success->token;
		//get user by $token from API
		$userData = $this->httpMethods->get($token, $this->route . '/user');
		//get http code and response message
		$httpCode = $data['info']['http_code'];
		$response = json_decode($userData['response']);
		//if 401 throw exception with error message
		if ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			throw new AuthenticationException($response->error);
		//if any code accept for 401 and 200 returned, throw exception
		} elseif($httpCode !== HttpStatus::STATUS_OK) {
			throw new AuthenticationException($httpCode . ': Something went wrong.');
		}
		//get user from response
		$user = $response->data;
		//create and return new Identity for user with id, email and token
        return new Identity($user->id, ['email' => $user->email, 'token' => $token]);
    }
}

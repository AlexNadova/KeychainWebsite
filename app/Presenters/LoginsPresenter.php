<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use App\Helpers\HttpStatus;
use App\HttpMethods\HttpMethods;

/**
 *  Summary 
 *  Presenter for logins list.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @since 36: get all logins
 */
final class LoginsPresenter extends Nette\Application\UI\Presenter
{
	/** @var HttpMethods $httpMethods*/
	private $httpMethods;

	/** @var string $route */
	public $route = 'http://127.0.0.1:8000/api/v1';

	function __construct(HttpMethods $httpMethods)
	{
		$this->httpMethods = $httpMethods;
	}

	/**
	 *  if user is not logged in, redirect him to registration/login page
     *  @since 36: get all logins
	 * 	@return void
	 */
	public function actionDefault(): void{
		if(!$this->getUser()->isLoggedIn()){
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}
	}
	
	/**
	 *  render list of logins to template
 	 *  @since 36: get all logins
	 * 	@return void
	 */
	public function renderDefault(): void{
		$user = $this->getUser();
		$this->template->email = $user->getIdentity()->email;
		$this->template->logins = $this->getLogins($user->getIdentity()->token, $user);
	}

	/**
	 *  get logins from API and return them as array
	 *  @since 36: get all logins
	 *  @param string $token - for authentication
	 *  @param \Nette\Security\User $user - in case of logout
	 * 	@return array - array of login objects
	 */
	public function getLogins($token, $user): array{
		//get logins from API
		$response = $this->httpMethods->get($token, $this->route . '/logins');
		$responseBody = $response['response'];
		$httpCode = $response['info']['http_code'];
		//if http code = 200, get data (array of logins) to $logins
		if ($httpCode === HttpStatus::STATUS_OK) {
			$logins = $responseBody->data;
			//logins are paginated so its possible that several Get requests need to be made
			while (!is_null($responseBody->next_page_url)){
				$response = $this->httpMethods->get($token, $responseBody->next_page_url);
				//merge response login array to $logins
				$logins = array_merge($logins, $responseBody->data);
				$responseBody = $response['response'];
			}
			return $logins;
		}elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}else {
			return [];
		}
	}
}
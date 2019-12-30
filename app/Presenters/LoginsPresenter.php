<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Helpers\HttpStatus;
use App\HttpMethods\HttpMethods;
use stdClass;

/**
 *  Summary 
 *  Presenter for logins list.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @since 36: get all logins
 *  @since 35: get login by id: added code for single login page
 */
final class LoginsPresenter extends Nette\Application\UI\Presenter
{
	/** @var HttpMethods $httpMethods*/
	private $httpMethods;

	/** @var string $route */
	public $route = 'http://127.0.0.1:8000/api/v1/logins';

	function __construct(HttpMethods $httpMethods)
	{
		$this->httpMethods = $httpMethods;
	}

	//----------------------------------------------GET ALL LOGINS----------------------------------------------

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
	 *  @since 35: get login by id: array merge fix
	 *  @param string $token - for authentication
	 *  @param \Nette\Security\User $user - in case of logout
	 * 	@return array - array of login objects | array - empty
	 */
	public function getLogins($token, $user): array{
		//get logins from API
		$response = $this->httpMethods->get($token, $this->route);
		$responseBody = $response['response'];
		$httpCode = $response['info']['http_code'];
		//if http code = 200, get data (array of logins) to $logins
		if ($httpCode === HttpStatus::STATUS_OK) {
			$logins = $responseBody->data;
			//logins are paginated so its possible that several Get requests need to be made
			while (!is_null($responseBody->next_page_url)){
				$response = $this->httpMethods->get($token, $responseBody->next_page_url);
				$responseBody = $response['response'];
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

	//----------------------------------------------GET LOGIN----------------------------------------------

	/**
	 *  if user is not logged in, redirect him to registration/login page
     *  @since 35: get login by id
	 * 	@return void
	 */
	public function actionLogin(): void{
		if(!$this->getUser()->isLoggedIn()){
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}
	}

	/**
	 *  render specific login
	 *  @since 35: get login by id 
	 *  @param int $id - login's id to get all its data 
	 * 	@return void
	 */
	public function renderLogin($id): void{
		$user = $this->getUser();
		$this->template->email = $user->getIdentity()->email;
		$this->template->login = $this->getLoginById($user->getIdentity()->token, $user, $id);
	}

	/**
	 *  get login's data by its id
	 *  @since 35: get login by id
	 *  @param string $token - for authentication
	 *  @param \Nette\Security\User $user - in case of logout
	 *  @param int $id - to find login
	 * 	@return stdClass {id, user_id, website_name, website_address, 
	 * 		username, password, created_at, updated_at}
	 */
	public function getLoginById($token, $user, $id): stdClass {
		//get logins from API
		$response = $this->httpMethods->get($token, $this->route . '/' . $id);
		$httpCode = $response['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_OK) {
			$login = $response['response']->data;
			return $login;
		}elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}else {
			$this->flashMessage('Cannot render login.', 'error');
			$this->redirect('Logins:default');
		}
	}

	/**
	 *  create login form for update
	 * 	@return Form
	 *  @since 35: get login by id
	 */
	public function createComponentLoginForm(): Form
	{
		$form = new Form();
		//CSRF protection
		$form->addProtection();
		$form->addText('websiteName')->setRequired('Website name is required')
			->addRule(Form::MAX_LENGTH, 'Website name can have maximum of 30 characters.', 30);
		$form->addText('websiteAddress')->setRequired('Website address is required')
			->addRule(Form::MAX_LENGTH, 'Website address can have maximum of 255 characters.', 255);
		$form->addText('username')->setRequired('Username is required')
			->addRule(Form::MAX_LENGTH, 'Username can have maximum of 45 characters.', 45);
		$form->addText('password')->setRequired('Password is required')
			->addRule(Form::MAX_LENGTH, 'Password can have maximum of 45 characters.', 45);
		$form->addText('createdAt');
		$form->addText('updatedAt');
		$form->addSubmit('update', 'UPDATE');
		$form->onSuccess[] = array($this, 'loginFormSuccess');
		return $form;
	}

	public function loginFormSuccess(Form $form, $values): void
	{ }
}
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
 *  @since 29: update login: update login code
 *  @since 30: delete login: delete login code
 *  @since 5: create new login: create login code
 */
final class LoginsPresenter extends Nette\Application\UI\Presenter
{
	/** @var HttpMethods $httpMethods*/
	private $httpMethods;

	/** @var string $route */
	public $route = 'http://127.0.0.1:8000/api/v1/logins';

	private $login;

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
	public function actionDefault(): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}
	}

	/**
	 *  render list of logins to template
	 *  @since 36: get all logins
	 * 	@return void
	 */
	public function renderDefault(): void
	{
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
	public function getLogins($token, $user): array
	{
		//get logins from API
		$response = $this->httpMethods->get($token, $this->route);
		$responseBody = $response['response'];
		$httpCode = $response['info']['http_code'];
		//if http code = 200, get data (array of logins) to $logins
		if ($httpCode === HttpStatus::STATUS_OK) {
			$logins = $responseBody->data;
			//logins are paginated so its possible that several Get requests need to be made
			while (!is_null($responseBody->next_page_url)) {
				$response = $this->httpMethods->get($token, $responseBody->next_page_url);
				$responseBody = $response['response'];
				//merge response login array to $logins
				$logins = array_merge($logins, $responseBody->data);
				$responseBody = $response['response'];
			}
			return $logins;
		} elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You have been logged out.', 'info');
			$this->redirect('Verification:default');
		} else {
			return [];
		}
	}

	//----------------------------------------------GET LOGIN----------------------------------------------

	/**
	 *  if user is not logged in, redirect him to registration/login page
	 * 	@param string $id - login's id
	 *  @since 35: get login by id
	 *  @since 29: update login: code remade to us $this->login
	 * 	@return void
	 */
	public function actionLogin($id): void
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}
		$user = $this->getUser();
		$this->template->email = $user->getIdentity()->email;
		$this->login = $this->getLoginById($user->getIdentity()->token, $user, $id);
		$this->template->login = $this->login;
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
	public function getLoginById($token, $user, $id): stdClass
	{
		//get logins from API
		$response = $this->httpMethods->get($token, $this->route . '/' . $id);
		$httpCode = $response['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_OK) {
			$login = $response['response']->data;
			return $login;
		} elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You have been logged out.', 'info');
			$this->redirect('Verification:default');
		} else {
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

	//----------------------------------------------UPDATE LOGIN----------------------------------------------

	/**
	 *  if login form was submitted successfully update login's data
	 *  @param Form $form, Nette\Utils\ArrayHash $values
	 * 	@return void
	 *  @since 29: update login
	 */
	public function loginFormSuccess(Form $form, $values): void
	{
		$user = $this->getUser();
		$token = $user->getIdentity()->token;
		$id = $this->login->id;

		$updatedLogin = array(
			'website_name' => $values->websiteName,
			'website_address' => $values->websiteAddress,
			'username' => $values->username,
			'password' => $values->password,
		);
		$data = $this->httpMethods->put($token, $this->route . '/' . $id, $updatedLogin);
		$httpCode = $data['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_OK) {
			$this->flashMessage($httpCode . ': Update successful.', 'success');
			$this->redirect('this');
		} elseif ($httpCode === HttpStatus::STATUS_UNPROCESSABLE_ENTITY) {
			$this->flashMessage($httpCode . ': ' . json_decode($data['response']->errors), 'error');
			$this->redirect('this');
		} elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You have been logged out.', 'info');
			$this->redirect('Verification:default');
		} else {
			$this->flashMessage($httpCode . ': Something went wrong.', 'error');
			$this->redirect('this');
		}
	}

	//----------------------------------------------DELETE LOGIN----------------------------------------------

	/**
	 *  action method do delete login, called from login.latte 
	 *  @param int $id - login's id
	 * 	@return void
	 *  @since 30: delete login
	 */
	public function actionDeleteLogin($id): void{
		$user = $this->getUser();
		$token = $user->getIdentity()->token;
		$response = $this->httpMethods->delete($token, $this->route . '/' . $id);
		$httpCode = $response['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_OK) {
			$this->flashMessage('Login deleted.', 'success');
			$this->redirect('Logins:default');
		} elseif ($httpCode === HttpStatus::STATUS_UNAUTHORIZED) {
			$user->logout();
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		} else {
			$this->flashMessage('Cannot render login.', 'error');
			$this->redirect('Logins:default');
		}
	}

	//----------------------------------------------CREATE LOGIN----------------------------------------------

	/**
	 *  render create login page
	 *  @since 5: create new login
	 * 	@return void
	 */
	public function renderCreateLogin(): void
	{
		$user = $this->getUser();
		$this->template->email = $user->getIdentity()->email;
	}

	/**
	 *  create form for adding login
	 * 	@return Form
	 *  @since 5: create new login
	 */
	public function createComponentCreateLoginForm(): Form
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
		$form->addSubmit('create', 'ADD');
		$form->onSuccess[] = array($this, 'createLoginFormSuccess');
		return $form;
	}

	/**
	 *  if login form was submitted successfully create new login
	 *  @param Form $form, Nette\Utils\ArrayHash $values
	 * 	@return void
	 *  @since 5: create new login
	 */
	public function createLoginFormSuccess(Form $form, $values): void
	{
		$user = $this->getUser();
		$token = $user->getIdentity()->token;

		$newLogin = array(
			'website_name' => $values->websiteName,
			'website_address' => $values->websiteAddress,
			'username' => $values->username,
			'password' => $values->password,
		);
		$data = $this->httpMethods->post($newLogin, $this->route, $token);
		$httpCode = $data['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_CREATED) {
			$this->flashMessage($httpCode . ': Login created.', 'success');
			$this->redirect('Logins:default');
		} elseif ($httpCode === HttpStatus::STATUS_UNPROCESSABLE_ENTITY) {
			$this->flashMessage($httpCode . ': ' . json_decode($data['response']->errors), 'error');
			$this->redirect('this');
		} else {
			$this->flashMessage($httpCode . ': Something went wrong.', 'error');
			$this->redirect('this');
		}
	}
}

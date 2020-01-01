<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Helpers\HttpStatus;
use App\HttpMethods\HttpMethods;

/**
 *  Summary 
 *  Presenter for user profile and all user related actions.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @since 39: edit account
 */
final class UserPresenter extends Nette\Application\UI\Presenter
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
	 *  If user is not logged in, redirect him to registration/login page.
	 * 	Token is used to get user data, if response code is 401, token doesn't exist in 
	 * 	DB and user should be logged out.
 	 *  @since 39: edit account
	 * 	@return void
	 */
	public function actionProfile(): void{
		if(!$this->getUser()->isLoggedIn()){
			$this->flashMessage('You need to be logged in to access this page.', 'denied');
			$this->redirect('Verification:default');
		}
		$user = $this->getUser();
		$this->template->email = $user->getIdentity()->email;
		$response = $this->httpMethods->get($user->getIdentity()->token, $this->route.'/user');
		if($response['info']['http_code'] === HttpStatus::STATUS_UNAUTHORIZED){
			$user->logout();
			$this->flashMessage('You have been logged out.', 'info');
			$this->redirect('Verification:default');
		}
		$this->template->user = $response['response']->data;
	}

	/**
	 *  form for updating name and surname
	 *  @since 39: edit account
	 *  @return Form
	 */
	public function createComponentUserForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->addText('name')->setRequired('Name is required')
			->addRule(Form::PATTERN, 'Name has to have 2-17 letters.', '[a-zA-Zá-žÁ-Ž]{2,17}');
		$form->addText('surname')->setRequired('Surname is required')
			->addRule(Form::PATTERN, 'Surname has to have 2-17 letters.', '[a-zA-Zá-žÁ-Ž]{2,17}');
		$form->addSubmit('update', 'UPDATE');
		$form->onSuccess[] = array($this, 'userFormSuccess');
		return $form;
	}

	/**
	 *  form for updating password
	 *  @since 39: edit account
	 *  @return Form
	 */
	public function createComponentPasswordForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->addPassword('password')->setRequired('Password is required')->addRule(Form::PATTERN,
			'Password has to contain at least one uppercase and one lowercase letter and one number.',
			'^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$'
		);
		$form->addPassword('c_password')->setRequired('Password repeat is required')
			->addRule(Form::PATTERN, 'Password repeat has to match password.', $form['password']);
		$form->addSubmit('update', 'UPDATE');
		$form->onSuccess[] = array($this, 'userFormSuccess');
		return $form;
	}

	/**
	 *  if form was submitted successfully, create user array with different values (depends 
	 * 	on what was updated), then update user.
	 *  @since 39: edit account
	 *  @param Form $form, $values - input values
	 *  @return void
	 */
	public function userFormSuccess(Form $form, $values): void
	{
		if(isset($values->name)){
			$user['name'] = $values->name;
		}
		if(isset($values->surname)){
			$user['surname'] = $values->surname;
		}
		if(isset($values->email)){
			$user['email'] = $values->email;
		}
		if(isset($values->password) && isset($values->c_password)){
			$user['password'] = $values->password;
			$user['c_password'] = $values->c_password;
		}
		$token = $this->getUser()->getIdentity()->token;
		$data = $this->httpMethods->put($token, $this->route.'/user', $user);
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

	/**
	 *  form for updating email
	 *  @since 39: edit account
	 *  @return Form
	 */
	public function createComponentEmailForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->addEmail('email')->setRequired('Email is required');
		$form->addSubmit('update', 'UPDATE');
		$form->onSuccess[] = array($this, 'emailFormSuccess');
		return $form;
	}

	/**
	 *  if email form was submitted successfully, create user array and update user.
	 * 	User finishes email update when he clicks on link sent in email from API.
	 *  @since 39: edit account
	 *  @param Form $form, $values - input values
	 *  @return void
	 */
	public function emailFormSuccess(Form $form, $values): void
	{
		$user = $this->getUser();
		$updateUser = [
			'email' => $user->getIdentity()->email,
			'email_update' => $values->email
		];
		$token = $user->getIdentity()->token;
		$data = $this->httpMethods->post($updateUser, $this->route.'/email/update', $token);
		$httpCode = $data['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_OK) {
			$this->flashMessage($httpCode . ': We have sent you a verification mail.', 'success');
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
}
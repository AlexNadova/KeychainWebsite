<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Exception;
use App\Helpers\HttpStatus;
use App\HttpMethods\HttpMethods;
use App\UserAuthenticator;

/**
 *  Summary 
 *  Presenter for verification (registration and login). It's used for creating
 * 	registration and login forms and deciding what to do when forms are submitted.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @access public
 *  @since 37:register to account
 *  @since 38:login to account: login code added
 */
final class VerificationPresenter extends Nette\Application\UI\Presenter
{
	/** @var UserAuthenticator $authenticator - for custom user authentication*/
	private $authenticator;

	/** @var HttpMethods $httpMethods*/
	private $httpMethods;

	/** @var string $route */
	public $route = 'http://127.0.0.1:8000/api/v1';

	function __construct(HttpMethods $httpMethods, UserAuthenticator $authenticator)
	{
		$this->httpMethods = $httpMethods;
		$this->authenticator = $authenticator;
	}

	//--------------------------------------------REGISTRATION--------------------------------------------

	/**
	 *  create registration form
	 * 	@return Form
	 */
	public function createComponentRegistrationForm(): Form
	{
		$form = new Form();
		//CSRF protection
		$form->addProtection();
		//add name, surname, email, password and c_password to form
		$form->addText('name')->setRequired('Name is required')
			->addRule(Form::PATTERN, 'PIN has to have 4 digits', '[a-zA-Zá-žÁ-Ž]{2,17}');;
		$form->addText('surname')->setRequired('Surname is required')
			->addRule(Form::PATTERN, 'PIN has to have 4 digits', '[a-zA-Zá-žÁ-Ž]{2,17}');;
		$form->addEmail('email')->setRequired('Email is required');
		$form->addPassword('password')->setRequired('Password is required')->addRule(Form::PATTERN,
			'Password has to contain at least one uppercase and one lowercase letter and one number.',
			'^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$'
		);
		$form->addPassword('c_password')->setRequired('Password repeat is required')
			->addRule(Form::PATTERN, 'Password repeat has to match password.', $form['password']);
		//add submit button
		$form->addSubmit('register', 'REGISTER');
		//if submit is successful, continue to registrationFormSuccess function
		$form->onSuccess[] = array($this, 'registrationFormSuccess');
		return $form;
	}

	/**
	 *  if registration form was submitted successfully
	 *  @param Form $form, Nette\Utils\ArrayHash $values
	 * 	@return void
	 */
	public function registrationFormSuccess(Form $form, $values): void
	{
		//user array with values from form
		$user = array(
			'name' => $values->name,
			'surname' => $values->surname,
			'email' => $values->email,
			'password' => $values->password,
			'c_password' => $values->c_password
		);
		//post these values to /register route
		$data = $this->httpMethods->post($user, $this->route . '/register');
		//get http code from response
		$httpCode = $data['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_CREATED) {
			$this->flashMessage($httpCode . ': Registration successful.', 'success');
			//TO-DO: email verification pages
			$this->redirect('this');
		//values were in wrong format, type etc.
		} elseif ($httpCode === HttpStatus::STATUS_UNPROCESSABLE_ENTITY) {
			$this->flashMessage($httpCode . ': ' . $data['response'], 'error');
			$this->redirect('this');
		} else {
			$this->flashMessage($httpCode . ': Something went wrong.', 'error');
			$this->redirect('this');
		}
	}

	//--------------------------------------------LOGIN--------------------------------------------

	/**
	 *  create login form
	 * 	@return Form
	 *  @since 38:login to account
	 */
	public function createComponentLoginForm(): Form
	{
		$form = new Form();
		//CSRF protection
		$form->addProtection();
		//add email, password and submit button to form
		$form->addEmail('email')->setRequired('Email is required');
		$form->addPassword('password')->setRequired('Password is required');
		$form->addSubmit('login', 'LOGIN');
		//if submit is successful, continue to loginFormSuccess function
		$form->onSuccess[] = array($this, 'loginFormSuccess');
		return $form;
	}

	/**
	 *  if login form was submitted successfully
	 *  @param Form $form, Nette\Utils\ArrayHash $values
	 * 	@return void
	 *  @since 38:login to account
	 */
	public function loginFormSuccess(Form $form, $values): void
	{
		try {
			//user authentication  
			$user = $this->getUser();
			$user->setAuthenticator($this->authenticator);
			$user->login($values->email, $values->password);
			// $user->setExpiration('20 minutes');
			//if exception is caught, access denied
		} catch (Exception $e) {
			$this->flashMessage($e, "denied");
			$this->redirect("this");
		}
		$this->flashMessage('Login successful.', 'success');
		// TO-DO: redirect to user page
		$this->redirect('this');
	}
}

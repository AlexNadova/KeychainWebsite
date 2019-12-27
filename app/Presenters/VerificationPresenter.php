<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Exception;
use App\Helpers\HttpStatus;
use App\HttpMethods\HttpMethods;

final class VerificationPresenter extends Nette\Application\UI\Presenter
{

	/** @var HttpMethods $httpMethods*/
	private $httpMethods;

	public $route = 'http://127.0.0.1:8000/api/v1';

	function __construct(HttpMethods $httpMethods)
	{
		$this->httpMethods = $httpMethods;
	}

//--------------------------------------------REGISTRATION--------------------------------------------

	public function createComponentRegistrationForm(): Form
	{
		$form = new Form();
		$form->addProtection();
		$form->addText('name')->setRequired('Name is required')
			->addRule(Form::PATTERN, 'PIN has to have 4 digits', '[a-zA-Zá-žÁ-Ž]{2,17}');;
		$form->addText('surname')->setRequired('Surname is required')
			->addRule(Form::PATTERN, 'PIN has to have 4 digits', '[a-zA-Zá-žÁ-Ž]{2,17}');;
		$form->addEmail('email')->setRequired('Email is required');
		$form->addPassword('password')->setRequired('Password is required')->addRule(Form::PATTERN,
			'Password has to contain at least one uppercase and one lowercase letter and one number.',
			'^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$');
		$form->addPassword('c_password')->setRequired('Password repeat is required')
			->addRule(Form::PATTERN, 'Password repeat has to match password.', $form['password']);
		$form->addSubmit('register', 'REGISTER');

		$form->onSuccess[] = array($this, 'registrationFormSuccess');
		return $form;
	}
	
	public function registrationFormSuccess(Form $form, $values)
	{
		$user = array(
			'name' => $values->name,
			'surname' => $values->surname,
			'email' => $values->email,
			'password' => $values->password,
			'c_password' => $values->c_password
		);
		$data = $this->httpMethods->post($user, $this->route . '/register');
		$httpCode = $data['info']['http_code'];
		if ($httpCode === HttpStatus::STATUS_CREATED) {
			$this->flashMessage($httpCode . ': Registration successful.', 'success');
			$this->redirect('this');
		} elseif ($httpCode === HttpStatus::STATUS_UNPROCESSABLE_ENTITY) {
			$this->flashMessage($httpCode . ': ' . $data['response'], 'error');
			$this->redirect('this');
		} else {
			$this->flashMessage($httpCode . ': Something went wrong.', 'error');
			$this->redirect('this');
		}
	}
}

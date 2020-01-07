Peachenka
=================

This website for password manager project was constructed as final project for Computer science 
AP Degree at University college of Northern Denmark, in collaboration with Biddingtools Group.


Nette
------------

[Nette](https://nette.org) is a popular tool for PHP web development.
It is designed to be the most usable and friendliest as possible. It focuses
on security and performance and is definitely one of the safest PHP frameworks.

If you like Nette, **[please make a donation now](https://nette.org/donate)**. Thank you!


Requirements
------------

- Web Project for Nette 3.0 requires PHP 7.1


Download
------------

The project can be downloaded with following command:

	$ git clone https://github.com/AlexNadova/KeychainWebsite.git


Web Server Setup
----------------

The simplest way to get started is to start the built-in PHP server in the root directory of your project:

	php -S localhost:8000 -t www

Then visit `http://localhost:8000/verification` in your browser to acces the website.

For Apache or Nginx, setup a virtual host to point to the `www/` directory of the project and you
should be ready to go.


Notice: Composer PHP version
----------------------------

If you don't have Composer yet, download it following [the instructions](https://doc.nette.org/composer).
This project forces PHP 5.6 (eventually 7.1) as your PHP version for Composer packages. If you have newer 
version on production server you should change it in `composer.json`:

```json
"config": {
	"platform": {
		"php": "7.2"
	}
}
```

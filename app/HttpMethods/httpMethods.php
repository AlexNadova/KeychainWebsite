<?php

namespace App\HttpMethods;

/**
 *  Summary 
 *  Class used for http methods. Helps with code reuse.
 * 
 *  @author Alexandra Nadova <alexandranadova@gmail.com>
 *  @access public
 *  @since 37:register to account
 *  @since 38:login to account: added get function
 *  @since 57:headers: response decoded here instead of presenters
 */
class HttpMethods
{
	/**
	 *  post given $data to given $route, return response and info about response
	 *  @param mixed[] $data - array to be sent to API
	 *  @param string $route - string route that $data is sent to
	 * 	@return array - array of response and info about response
	 *  @since 37:register to account
	 */
	public function post($data, $route): array
	{
		//array to json
		$jsonData = json_encode($data);
		//instantiate an instance of cURL,which returns a cURL resource
		$ch = curl_init();
		//URL to send request to
		curl_setopt($ch, CURLOPT_URL, $route);
		//Return the response as a string instead of outputting it to the screen
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//what to post in body
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		//set headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData),
			'Accept: application/json'
		));
		//execute cURL request and save result and decode it
		$response['response'] = curl_exec($ch);
		$response['response'] = json_decode($response['response']);
		//get info about result
		$response['info'] = curl_getinfo($ch);
		//close cURL request
		curl_close($ch);
		return $response;
	}

	/**
	 *  get request, return response and info about response
	 *  @param string $token - token used in Authorization header needed to access API's functionalities
	 *  @param string $route - string route that $data is sent to
	 * 	@return array - array of response and info about response
	 *  @see post function for more detailed documentation
	 *  @since 38:login to account
	 */
	public function get($token, $route): array
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $route);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//add access token to headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer ' . $token
		));
		$response['response'] = curl_exec($ch);
		$response['response'] = json_decode($response['response']);
		$response['info'] = curl_getinfo($ch);
		curl_close($ch);
		return $response;
	}
}

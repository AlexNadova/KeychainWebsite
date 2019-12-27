<?php

namespace App\HttpMethods;

class HttpMethods
{
	public function post($data, $route)
	{
		$jsonData = json_encode($data);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $route);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($jsonData),
			'Accept: application/json'
		));
		$data['response'] = curl_exec($ch);
		$data['info'] = curl_getinfo($ch);
		curl_close($ch);
		return $data;
	}

}

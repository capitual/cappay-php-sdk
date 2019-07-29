<?php

namespace Capitual;

class CappayError extends \Error {}

class CapPay {

	public $id;
	public $url;

	public $merchant;
	public $wallet;
	public $currency;
	public $value;
	public $payee;
	public $expires;
	public $ipn;
	public $description;
	public $status;

	protected $capitual_base = 'https://api.capitual.com/v1.0';
	protected $cappay_base = 'https://pay.capitual.com/v1.0';

	static $STATUS_PENDING = 'pending';
	static $STATUS_PAID = 'paid';
	static $STATUS_CANCELED = 'canceled';
	static $STATUS_EXPIRED = 'expired';

	function __construct($invoice_id = false) {
		if ($invoice_id) {
			$this->id = $invoice_id;
			$this->getStatus();
		}
		return $this;
	}

	function create() {
		$get = $this->cappay_request('create', [
			'merchant' => $this->merchant,
			'wallet' => $this->wallet,
			'currency' => $this->currency,
			'value' => $this->value,
			'payee' => $this->payee,
			'expires' => $this->expires,
			'ipn' => $this->ipn,
			'description' => $this->description
		]);

		$get = json_decode($get);
		if ($get->error)
			throw new CappayError($get->error);
		else {
			$url = $get->data;
			$this->url = $url;
			$this->id = substr(parse_url($url, PHP_URL_PATH), 5);
			$this->status = 'pending';
		}

		return $this;
	}

	function getStatus() {
		$res = $this->cappay_request('details/'.$this->id);

		$res = json_decode($res);

		if ($res->error)
			throw new CappayError($res->error);
		else {
			$this->url = 'https://my.capitual.com/pay/'.$this->id;
			$this->merchant = $res->data->merchant;
			$this->wallet = $res->data->wallet;
			$this->currency = $res->data->currency;
			$this->value = $res->data->value;
			$this->expires = $res->data->expires;
			$this->ipn = $res->data->ipn;
			$this->description = $res->data->description;
			$this->status = $res->data->status;
		}
	}

	function getShortLink() {
		$res = $this->capitual_request('invoices/getInvoiceLink', [
			'invoice_id' => $this->id
		]);

		$res = json_decode($res);

		if ($res->error)
			throw new CappayError($res->error);
		else
			return $res->data;
	}

	private function cappay_request($path, $args = false) {
		return $this->http_request($this->cappay_base.'/'.$path, $args);
	}

	private function capitual_request($path, $args = false) {
		return $this->http_request($this->capitual_base.'/'.$path, $args);
	}

	private function http_request($url, $args = false) {
		if (function_exists('curl_init')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);

			if ($args) {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
			}

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

			$res = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);

			if ($err)
				throw new CappayError($err);
			else
				return $res;
		} else {
			$opts = array('http' =>
			    array(
			        'method'  => $args ? 'POST' : 'GET',
					'header' => "Content-type: application/x-www-form-urlencoded\r\n".
						    "User-Agent: Mozilla (CapVault)/1.0.0\r\n",
			        'content' => http_build_query($args),
				'timeout' => 30,
				'ignore_errors' => true
			    )
			);

			if ($args)
				$opts['http']['content'] = http_build_query($args);

			$context  = stream_context_create($opts);

			return file_get_contents($url, false, $context);
		}
	}
}
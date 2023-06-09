<?php
namespace Northplay\NorthplayApi\Controllers\Integrations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Northplay\NorthplayApi\Controllers\Integrations\ProxyController;
use Illuminate\Support\Str;
use Northplay\NorthplayApi\Models\SoftswissGameModel;
use Northplay\NorthplayApi\Models\GatewayParentSessions;
use Northplay\NorthplayApi\Models\GatewayEntrySessions;
use Illuminate\Support\Facades\Crypt;
use Northplay\NorthplayApi\Controllers\Casino\API\Auth\UserBalanceController;

trait GatewayTrait
{

	public function proxy(Request $request, $url)
	{
			 $proxy_controller = new ProxyController;
			 return $proxy_controller->CreateProxy($request)->toUrl($url);
	}

	public function uuid()
	{
			 return Str::uuid();
	}
	
	public function secret_key()
	{
			 return "d68cb363-0303-4c34-951a-9a7c2fed451e";
	}
	
	public function hmac($input)
	{
			return hash_hmac('md5', $input, $this->secret_key());
	}

	public function user_balance($session_id) 
	{
		$user_balance = new UserBalanceController;

		$session = $this->select_parent_session($session_id);
		return $user_balance->get_user_balance($session->user_public_id, $session->currency);	
		
	}

	public function process_game($session_id, $betAmount, $winAmount, $data = NULL)
	{
		
		$debit_completed = 0;
		$credit_completed = 0;
		$session = $this->select_parent_session($session_id);

		if($betAmount > 0) {
			$user_balance = $this->user_balance($session_id);
			$debit_completed = "insufficient_funds";
			if($user_balance < $betAmount) {
				if($this->is_development_state()) {
					save_log("GatewayTrait", "Tried to charge user more then he has in game event: ".json_encode($data));
					abort(400, "User has insufficient funds to process game: ".json_encode($data));
				}
				return "insufficient funds";
			}
			$debit_completed = $this->user_balance_transaction($session_id, "debit", (int) $betAmount, "Slot game event", array("session" => $session, "game_data" => $data));
		}
		
		if($winAmount > 0) {
			if($debit_completed !== "insufficient_funds") {
				$credit_completed = $this->user_balance_transaction($session_id, "credit", (int) $winAmount, "Slot game event", array("session" => $session, "game_data" => $data));
			}
		}
		
		return $this->user_balance($session_id);
	}
	
	public function user_balance_transaction($session_id, $direction, $amount, $tx_description, $tx_data = NULL)
	{
		$session = $this->select_parent_session($session_id);

		$user_balance = new UserBalanceController;
		
		if($direction === "credit") {
			return $user_balance->credit_user_balance($session->user_public_id, $session->currency, $amount, $tx_description, $tx_data);
		} 
		if($direction === "debit")  {
			return $user_balance->debit_user_balance($session->user_public_id, $session->currency, $amount, $tx_description, $tx_data);
		}
		
		abort(400, "You can only debit or credit user balance");		
	}

	public function random_user_agent() 
	{
		$list = [
				"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36",
				"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36 Edg/111.0.1661.54",
				"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36 Edg/112.0.0.0",
				"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36",
				"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36 OPR/96.0.0.0",
		];
		return $list[rand(0, 8)];
	}


	public function get_redirect_url($url) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, "'".$this->random_user_agent()."'");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$html = curl_exec($ch);
			$redirectURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
			curl_close($ch);
			return $redirectURL;
	}
	
	public function select_game($input)
	{
			$game_model = new SoftswissGameModel;
			$select_game = $game_model->where("id", $input)->first();
			if(!$select_game) {
				$select_game = $game_model->where("slug", $input)->first();
				if(!$select_game) {
					save_log("GatewayTrait", $input." game not found being called select_game()");
					abort(400, "Game not found");
				}
			}
			return $select_game;
	}

	public function encrypt_string($string)
	{
			 $encrypted = Crypt::encryptString($string);
			 return $encrypted;
	}

	public function decrypt_string($string)
	{
			 $decrypt= Crypt::decryptString($string);
			 return $decrypt;
	}

	public function build_query($query)
	{
			$resp = http_build_query($query);
			$resp = urldecode($resp);
			return $resp;
	}

	public function parse_query($query_string)
	{
			parse_str($query_string, $q_arr);
			return $q_arr;
	}


	public function url_hostname($url)
	{
		return parse_url($url, PHP_URL_HOST);
	}

	public function url_fullpath($url)
	{
		return parse_url($url, PHP_URL_PATH);
	}

	public function url_params($url)
	{
		$parts = parse_url($url);
		return parse_str($parts['query'], $query);
	}
	public function select_provider($provider)
	{
			$game_kernel = new \Northplay\NorthplayApi\Controllers\Integrations\Games\GameKernel;
			$provider = (collect($game_kernel->providers()))->where("id", $provider)->first();
			if($provider) {
				return $provider;
			}
	}

	public function select_parent_session($session_id)
	{
			$parent_session_model = new GatewayParentSessions;
			$select_session = $parent_session_model->where("session_id", $session_id)->first();
			if(!$select_session) {
				save_log("GatewayTrait", "Parent session with ID: ".$session_id." not found.");
				abort(400, "Parent session not found.");
			}
			return $select_session;
	}

	public function update_parent_session($session_id, $key, $value)
	{
			$parent_session_model = new GatewayParentSessions;
			$select_session = $parent_session_model->where("session_id", $session_id)->first();

			if(!$select_session) {
				save_log("GatewayTrait", "Parent session ${session_id} not found when being called update_parent_session.");
				abort(400, "Entry session not found.");
			}

			$select_session->update([
					$key => $value
			]);
			return $this->select_parent_session($session_id);
	}

	public function json_validator($input)
	{
			if (!empty($data)) {
					return is_string($data) && 
						is_array(json_decode($data, true)) ? true : false;
			}
			return false;
	}

	public function is_development_state() {
			if(env("APP_ENV") === "development") {
				return true;
			}
			if(env("APP_DEBUG") === true) {
				return true;
			}
			return false;
	}

	public function get_parent_session_storage($session_id)
	{
			$storage = $this->select_parent_session($session_id)->storage;
			
			if($this->json_validator($storage)) {
				$storage = json_decode($storage, true);
			}
			return $storage;
	}

	public function upsert_parent_session_storage($session_id, $storage_key, $storage_value)
	{
				$parent_session_model = new GatewayParentSessions;
				$select_session = $parent_session_model->where("session_id", $session_id)->first();
				if($select_session) {
					$current_storage = $select_session->storage;
					$current_storage[$storage_key] = $storage_value;
					save_log("UpsertParentSession", $current_storage);
					$select_session->where("session_id", $session_id)->update([
							"storage" => json_encode($current_storage, JSON_PRETTY_PRINT)
					]);
					return $this->select_parent_session($session_id);
				}
	}


	public function select_entry_session($entry_token)
	{
		$entrysession_model = new GatewayEntrySessions;
		$select_entry_session = $entrysession_model->where("entry_token", $entry_token)->first();
		if(!$select_entry_session) {
			save_log("GatewayTrait", "Entry session ${entry_token} not found when being called update_entry_session.");
			abort(400, "Entry session not found.");
		}
		return $entry_token;
	}
	
	public function update_entry_session($entry_token, $key, $value)
	{
			$entrysession_model = new GatewayEntrySessions;
			$select_entry_session = $entrysession_model->where("entry_token", $entry_token)->first();
			if(!$select_entry_session) {
				save_log("GatewayTrait", "Entry session ${entry_token} not found when being called update_entry_session.");
				abort(400, "Entry session not found.");
			}
			$select_entry_session->update([
					$key => $value
			]);
			return $this->select_entry_session($entry_token);
	}

}
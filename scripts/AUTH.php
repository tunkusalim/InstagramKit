<?php  
/**
* Instagram Auth v1.0
* Last Update 21 Juli 2020
* Author : Faanteyki
*/
require "../vendor/autoload.php";

date_default_timezone_set('Asia/Jakarta');

use Riedayme\InstagramKit\InstagramAuthAPI;
use Riedayme\InstagramKit\InstagramChecker;

Class Auth 
{

	public $filesavedata = './data/user.json';

	public function GetInputUsername() 
	{

		echo "[?] Masukan Username : ";

		$input = trim(fgets(STDIN));

		return $input;
	}	

	public function GetInputPassword() 
	{

		echo "[?] Masukan Password : ";

		/** 
		 * hidden password
		 * https://gist.github.com/scribu/5877523
		 */			
		echo "\033[30;40m";  
		$input = trim(fgets(STDIN));
		echo "\033[0m";     

		return $input;
	}

	public function GetInputRelog($data = false) 
	{

		echo "[?] Apakah anda ingin relogin akun ini (y/n) : ";

		$input = trim(fgets(STDIN));

		if (!in_array(strtolower($input),['y','n'])) 
		{
			die("Pilihan tidak diketahui");
		}

		return $input;
	}

	public function GetInputChoiceVerify() 
	{

		echo "[•] Pilih cara vertifikasi : ".PHP_EOL;
		echo "[1] Kirim kode ke nomor handphone".PHP_EOL;
		echo "[2] Kirim kode ke email".PHP_EOL;
		echo "[?] Pilihan anda (1/2) : ";

		$input = trim(fgets(STDIN));

		if (!in_array(strtolower($input),['1','2'])) 
		{
			die("Pilihan tidak ditemukan");
		}

		return $input;
	}

	public function GetInputSecurityCode() 
	{

		echo "[?] Masukan Kode Vertifikasi : ";

		return trim(fgets(STDIN));
	}	

	public function SaveData($data){

		$filename = $this->filesavedata;

		if (file_exists($filename)) 
		{
			$read = file_get_contents($filename);
			$read = json_decode($read,true);
			$dataexist = false;
			foreach ($read as $key => $logdata) 
			{
				if ($logdata['userid'] == $data['userid']) 
				{
					$inputdata[] = $data;
					$dataexist = true;
				}else{
					$inputdata[] = $logdata;
				}
			}

			if (!$dataexist) 
			{
				$inputdata[] = $data;
			}
		}else{
			$inputdata[] = $data;
		}

		return file_put_contents($filename, json_encode($inputdata,JSON_PRETTY_PRINT));
	}

	public function ReadSavedData()
	{

		$filename = $this->filesavedata;

		if (file_exists($filename)) 
		{
			$read = file_get_contents($filename);
			$read = json_decode($read,TRUE);
			foreach ($read as $key => $logdata) 
			{
				$inputdata[] = $logdata;
			}

			return $inputdata;
		}else{
			return false;
		}
	}

	public function ReadData($data)
	{

		$filename = $this->filesavedata;

		if (file_exists($filename)) 
		{
			$read = file_get_contents($filename);
			$read = json_decode($read,TRUE);
			foreach ($read as $key => $logdata) 
			{
				if ($key == $data) 
				{
					$inputdata = $logdata;
					break;
				}
			}

			return $inputdata;
		}else{
			die("file tidak ditemukan");
		}
	}	

	public function New_Login($username,$password)
	{

		echo "[•] Login Menggunakan Username dan Password".PHP_EOL;

		$results = InstagramAuthAPI::Login($username,$password,'./data/');

		if (!$results['status']) 
		{
			die($results['response']);
		}
		elseif ($results['status'] == 'checkpoint') 
		{

			echo "[!!!] Akun anda terkena checkpoint".PHP_EOL;

			$required = $results['response'];

			$choiceverify = self::GetInputChoiceVerify();
			$choiceverify = ($choiceverify == 1 ? 0 : 1);
			$sendCode = InstagramAuthAPI::CheckPointSend($required,$choiceverify);

			if (!$sendCode['status']) die($sendCode['response']);

			echo "[•] {$sendCode['response']}".PHP_EOL;

			$is_connected       = false;
			$is_connected_count = 1;

			do {

				$required['security_code'] = self::GetInputSecurityCode();
				$results = InstagramAuthAPI::CheckPointSolve($required);

				if ( $is_connected_count == 3 ) 
				{
					echo "[!] 3x Kode Salah, ERROR".PHP_EOL;
					die($results['response']);
				}

				if ($results['status'])
				{
					$is_connected = true;
				}else{
					echo "[!] Kode Salah, coba lagi".PHP_EOL;
				}

				$is_connected_count += 1;
			} while ( ! $is_connected );

		}

		/* success login without checkpoint */

		echo "[•] Menyimpan Data Login".PHP_EOL;

		$results = $results['response'];
		$results['password'] = $password;
		self::SaveData($results);

		return $results;
	}

	public function Old_Login($key) 
	{

		$results = self::ReadData($key);

		echo "[•] Check Live Cookie".PHP_EOL;

		$check_cookie = InstagramChecker::CheckLiveCookie($results['cookie']);
		if (!$check_cookie['status']) 
		{
			$results = self::New_Login($results['username'],$results['password']);
		}

		return $results;
	}

	public function Run($reauth = false)
	{

		if ($reauth) 
		{
			return self::New_Login($reauth['username'],$reauth['password']);
		}
		elseif ($check = self::ReadSavedData() AND !$reauth) 
		{

			echo "[?] Anda Memiliki Cookie yang tersimpan pilih angkanya dan gunakan kembali : ".PHP_EOL;

			foreach ($check as $key => $cookie) 
			{
				echo "[{$key}] ".$cookie['username'].PHP_EOL;

				$data_cookie[] = $key;
			}

			echo "[x] Masuk menggunakan akun baru".PHP_EOL;

			echo "[?] Pilihan Anda : ";

			$input = strtolower(trim(fgets(STDIN)));			

			if ($input != 'x') 
			{

				if (strval($input) !== strval(intval($input))) 
				{
					die("Salah memasukan format, pastikan hanya angka");
				}

				if (!in_array($input, $data_cookie)) 
				{
					die("Pilihan tidak ditemukan");
				}

				return self::Old_Login($input);

			}else{

				$username = Auth::GetInputUsername();
				$password = Auth::GetInputPassword();

				return self::New_Login($username,$password);		
			}
		}else{

			$username = Auth::GetInputUsername();
			$password = Auth::GetInputPassword();

			return self::New_Login($username,$password);
		}
	}
}
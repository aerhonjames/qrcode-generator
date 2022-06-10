<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class Qrcode_generator{

	protected $ci;
	protected $qrcode;
	protected $errors = [];
	protected $folder_path = NULL;
	protected $base_path = 'uploads/';
	protected $config;
	protected $allowed_config = [
		'color' => ['foreground', 'background'],
		'logo' => ['enable', 'path', 'size']
	];

	function __construct(){
		$this->ci =& get_instance();
		$this->ci->load->config('qrcode');
	}

	function config($config=[]){
		if(is_array($config) AND count($config)){
			foreach($config as $index=>$value){
				if(array_key_exists($index, $this->allowed_config)){
					$this->config[$index] = $value;
				}
			}

			print_array($this->config, 1);
		}
	}

	function folder_path($path=NULL){
		if($path){
			$this->folder_path = $path;
			return $this;
		}

		return $this->folder_path;
	}

	function generate($text=NULL, $size=300){

		if(is_null($text)) $this->errors[] = 'Invalid text to generate.';
		if(!$size AND (int)$size < 100) $this->errors[] = 'Size must be greater than 100px';

		if(!$this->has_error()){
			$this->qrcode = new QrCode($text);
			$this->qrcode->setSize($size);
			$this->qrcode->setMargin(10); 

			// Set advanced options
			$this->qrcode->setWriterByName('png');
			$this->qrcode->setEncoding('UTF-8');
			$this->qrcode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
			$this->qrcode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
			$this->qrcode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
			// $this->qrcode->setLabel('Scan the code', 16);
			// $this->qrcode->setLogoPath('assets/imgs/site/layout/master_berocca_logo.png');
			// $this->qrcode->setLogoSize(150);
			$this->qrcode->setValidateResult(true);
		}

		return $this;
	}

	function download($filename='qrcode'){
		if(!$this->qrcode instanceOf QrCode) $this->errors[] = 'Please generate first.';

		if(!$this->has_error()){
			$dir = './temp';

			if(!is_dir($dir)) mkdir($dir);

			$file_path = $dir.sprintf('/%1$s.png', $filename);
			$this->qrcode->writeFile($file_path);

			if(file_exists($file_path)){
				$content = read_file($file_path);
				unlink($file_path);
				rmdir($dir);

				force_download($filename.'.png', $content);	
			}

			return FALSE;
		}

	}

	function write($file_path='qrcode'){
		if(!$file_path) $this->errors[] = 'No file path provided.';

		if(!$this->has_error()){
			if(string_contains($file_path, '/')){
				$file_segment = explode('/', $file_path);
				$file_name = end($file_segment);

				array_pop($file_segment);
				$folder_path = implode('/', $file_segment);

				if($folder_path){
					$this->folder_path($folder_path);
				}

			}
			else $file_name = $file_path;

			$this->check_create_folder(); // check and create folder if the path not exists
			$file_name = sprintf('%1$s.png', $file_name);
			$generated_file_path = sprintf('%1$s%2$s', $this->generated_target_path(), $file_name);

			$this->qrcode->writeFile($generated_file_path);

			return $generated_file_path;
		}

		return NULL;
	}

	function view(){
		if(!$this->qrcode instanceOf QrCode) $this->errors[] = 'Please generate first.';

		if(!$this->has_error()){
			header('Content-Type: '.$this->qrcode->getContentType());
			echo $this->qrcode->writeString();
		}
	}

	function raw(){
		if(!$this->qrcode instanceOf QrCode) $this->errors[] = 'Please generate first.';

		if(!$this->has_error()){
			return $this->qrcode->writeDataUri();
		}
	}

	function errors(){
		return $this->errors;
	}

	function has_error(){
		if(count($this->errors)) return TRUE;
		return FALSE;
	}

	/*Helpers*/
	protected function generated_target_path(){
		$path = $this->folder_path();
		if(!string_contains($path, ['uploads'])){
			$path = $this->base_path.$path;
		}
		else $path = $path.'/';

		return $path;
	}

	protected function check_create_folder($path=NULL){
		$target_path = $this->generated_target_path();
		if(!is_dir($target_path)) {
			$path = explode('/', $target_path);
			$generated_path = [];

			foreach($path as $segment) {
				$generated_path[] = $segment;
				$target_path = join('/', $generated_path);
				if(!is_dir($target_path)) mkdir($target_path); 
			}
		}
	}
}
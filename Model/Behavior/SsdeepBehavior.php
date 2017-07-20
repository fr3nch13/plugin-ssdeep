<?php

class SsdeepBehavior extends ModelBehavior 
{
	// settings
	public $settings = array();

	//Default settings
	protected $_defaults = array();
	
	public $installed = false;
	
	public $ssdeep_fuzzy_hash_index = array();
	
	public $ssdeep_fuzzy_hash_filename_index = array();
	
	public $ssdeep_fuzzy_compare_index = array();

	public function setup(Model $Model, $config = array()) 
	{
	/**
	 * Configuration of Model
	 *
	 * @param AppModel $Model
	 * @param array $config
	 */
		$this->settings[$Model->alias] = array_merge($this->_defaults, $config);
		
		if(function_exists('ssdeep_fuzzy_compare') and function_exists('ssdeep_fuzzy_hash')) $this->installed = true;
	}
	
	public function ssdeep_determineWinner(Model $Model, $string = false, $options = array(), $return_option_index = false)
	{
		if(!$this->installed) return false;
		if(!$string) return false;
		if(!$options) return false;
		
		$string = trim($string);
		if(!$string) return false;
		
		$results = array();
		foreach($options as $i => $option)
		{
			if(is_array($option)) continue;
			
			if($return_option_index)
			{
				$result = $this->ssdeep_compareStrings($Model, $string, $i);
				$results[$i] = $result['percent'];
			}
			else
			{
				$result = $this->ssdeep_compareStrings($Model, $string, $option);
				$results[$option] = $result['percent'];
			}
		}
		
		arsort($results);
		reset($results);
		return key($results);
	}

	public function ssdeep_compareStrings(Model $Model, $string_1 = '', $string_2 = '') 
	{
	/**
	 * Compares 2 strings against each other
	 */
		$data = array(
	 		'string_1' => array(
	 			'string' => false,
	 			'hash' => false,
	 		),
	 		'string_2' => array(
	 			'string' => false,
	 			'hash' => false,
	 		),
	 		'percent' => false,
	 	);
	 	
	 	// ensure the pecl library for ssdeep is installed
	 	if(!$this->installed) return $data;
	 	
	 	// hash the strings
	 	$string_1 = trim($string_1);
	 	$string_2 = trim($string_2);
	 	
	 	if($string_1) 
	 	{
	 		$data['string_1']['string'] = $string_1;
	 		$data['string_1']['hash'] = $this->ssdeep_fuzzy_hash($Model, $string_1);
	 	}
	 	
	 	if($string_2)
	 	{
	 		$data['string_2']['string'] = $string_2;
	 		$data['string_2']['hash'] = $this->ssdeep_fuzzy_hash($Model, $string_2);
	 	}
	 	
	 	$data['percent'] = $this->ssdeep_fuzzy_compare($Model, $data['string_1']['hash'], $data['string_2']['hash']);
	 	
	 	return $data;
	}
	
	///////wrappers for the ssdeep pecl functions
	public function ssdeep_fuzzy_compare(Model $Model, $sig1 = false, $sig2 = false)
	{
		if(!$this->installed) return false;
		
		if(!trim($sig1)) return 0;
		
		if(!trim($sig2)) return 0;
		
		if(isset($this->ssdeep_fuzzy_compare_index[$sig1.$sig2]))
		{
			return $this->ssdeep_fuzzy_compare_index[$sig1.$sig2];
		}
		
		if(isset($this->ssdeep_fuzzy_compare_index[$sig2.$sig1]))
		{
			return $this->ssdeep_fuzzy_compare_index[$sig2.$sig1];
		}
		
		if($sig1 === $sig2)
		{
			$this->ssdeep_fuzzy_compare_index[$sig1.$sig2] = 100;
		}
		else
		{
			$this->ssdeep_fuzzy_compare_index[$sig1.$sig2] = ssdeep_fuzzy_compare($sig1, $sig2);
		}
		
		return $this->ssdeep_fuzzy_compare_index[$sig1.$sig2];
	}
	
	public function ssdeep_fuzzy_hash(Model $Model, $string = false)
	{
		if(!$this->installed) return false;
		
		if(!trim($string)) return false;
		
		if(isset($this->ssdeep_fuzzy_hash_index[$string]))
		{
			return $this->ssdeep_fuzzy_hash_index[$string];
		}
		
		$this->ssdeep_fuzzy_hash_index[$string] = ssdeep_fuzzy_hash($string);
		
		return $this->ssdeep_fuzzy_hash_index[$string];
	}
	
	public function ssdeep_fuzzy_hash_filename(Model $Model, $path = false)
	{
		if(!$this->installed) return false;
		
		if(!trim($path)) return false;
		
		if(!file_exists($path)) return false;
		
		if(!is_readable($path)) return false;
		
		if(isset($this->ssdeep_fuzzy_hash_filename_index[$path]))
		{
			return $this->ssdeep_fuzzy_hash_filename_index[$path];
		}
		
		$this->ssdeep_fuzzy_hash_filename_index[$path] = ssdeep_fuzzy_hash_filename($path);
		
		return $this->ssdeep_fuzzy_hash_filename_index[$string];
	}
}
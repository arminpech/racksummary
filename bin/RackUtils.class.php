<?php

/*
 * RackSummary Project
 *
 * This program can collect unit informations from different data sources
 * and creates a PDF output which displays the mounting positions of
 * units/systems in a rack.
 *
 * Copyright (c) 2011 Armin Pech
 *
 *
 * This file is part of RackSummary.
 *
 * RackSummary is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * RackSummary is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with RackSummary. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Last Update: 2011-03-05
 *
 * Website: http://projects.arminpech.de/racksummary/
 */


class RackUtils {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** program control attributes ***/
	private $program_exit_code=0;
	private $program_force_exit=true;
	private $program_verbose=false;


	/*** magic functions are unused ***/
	public function __construct() {
	}

	public function __destruct() {
	}


	/*** attribute handler functions ***/
	// handle class attibute values
	public function handle_exit_code($value=null) {
		if($value!==null) {
			if(!(is_integer($value) && $value>=0)) {
				$value=1;
			}
			$this->program_exit_code=$value;
			return $this;
		}
		return $this->program_exit_code;
	}

	public function handle_force_exit($value=null) {
		if($value!==null) {
			if($value===true) {
				$this->program_force_exit=true;
			}
			else {
				$this->program_force_exit=false;
			}
			return $this;
		}
		return $this->program_force_exit;
	}

	public function handle_verbose($value=null) {
		if($value!==null) {
			if($value===true) {
				$this->program_verbose=true;
			}
			else {
				$this->program_verbose=false;
			}
			return $this;
		}
		return $this->program_verbose;
	}

	// error'n'exit function
	public function err_exit($exit_code, $message=null) {
		if($this->handle_verbose()) {
			if($message===null) {
				$message='unknown error';
			}
			$class=debug_backtrace();
			if(isset($class[1]) && strlen($class[1]['class'])>0) {
				if(strlen($class[1]['function'])>0) {
					$class=$class[1]['class'].'::'.$class[1]['function'].'()';
				}
				else {
					$class=$class[1]['class'];
				}
				$class=' '.$class;
			}
			else {
				$class='';
			}
			echo 'ERROR['.$exit_code.']'.$class.': '.$message."!\n";
		}
		if($this->handle_force_exit()) {
			exit($this->handle_exit_code($exit_code)->handle_exit_code());
		}
	}

	// get absolute file name of given file name
	public function handle_file($value, $check_writeable=false) {
		if(!strlen($value)) {
			$this->err_exit(2, 'function expectes a file name');
		}
		// prepare absolute path for output file if caller (program) sets relative one
		if(substr($value, 0, 1)!='/') {
			$path=debug_backtrace();
			$value=dirname($path[count($path)-1]['file']).'/'.$value;
		}
		// check permissons of file's directory and file
		if(!file_exists(dirname($value))) {
			$this->err_exit(3, 'directory "'.dirname($value).'" of file "'.$value.'" does not exists');
		}
		if(file_exists($value)) {
			if($check_writeable && !is_writeable($value)) {
				$this->err_exit(4, 'file "'.$value.'" is not writeable');
			}
		}
		else {
			if($check_writeable) {
				if(!is_writeable(dirname($value))) {
					$this->err_exit(5, 'directory "'.dirname($value).'" is not writeable');
				}
			}
		}
		return $value;
	}
}

?>
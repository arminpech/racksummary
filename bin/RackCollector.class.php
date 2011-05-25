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
 * Last Update: 2011-05-07
 *
 * Website: http://projects.arminpech.de/racksummary/
 */

// set include path to applications base dir, means ../
set_include_path('..'.PATH_SEPARATOR.get_include_path());

// load global used util functions
require_once('RackUtils.class.php');


class RackCollector extends RackUtils {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** program control attributes ***/
	private $program_provider=null;


	/*** magic functions ***/
	public function __construct($provider=null, $verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
		// set provider (DAO)
		if($provider!==null) {
			$this->provider($provider);
		}
	}

	public function __destruct() {
	}

	// magic - call functions from provider class 8-o
	public function __call($name=null, $arguments=null) {
		$name=(String)$name;
		if(method_exists($this->provider(), $name)) {
			return call_user_func_array(array($this->provider(), $name), $arguments);
		}
		else {
			$this->err_exit(100, 'called function "'.$name.'" does not exist');
		}
	}


	/*** factory function ***/
	private function provider($value=null) {
		if($value!==null) {
			$value=(String)$value;
			if(!strlen($value)>0) {
				$this->err_exit(101);
			}
			$provider='RackCollector'.$value;
			@require_once('modules/collector/'.$provider.'.class.php');
			$this->program_provider=new $provider($this->handle_verbose());
			if(!($this->program_provider instanceof $provider)) {
				$this->err_exit(102, 'DAO "RackCollector'.$value.'" could not be created');
			}
			return $this;
		}
		return $this->program_provider;
	}
}

?>
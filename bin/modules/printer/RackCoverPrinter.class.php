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
 *
 * TODO: write your own design functions if needed
 */

// load global used util functions
require_once(dirname(__FILE__).'/../../RackUtils.class.php');


class RackCoverPrinter extends RackUtils {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** program control attributes ***/
	// PDF writer object
	private $module_writer=null;
	// handles if covers are printed
	private $module_activation=false;


	/*** magic functions ***/
	public function __construct($writer=null, $verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
		// set private writer of RackPrinter
		$this->writer($writer);
	}

	public function __destruct() {
	}


	// set writer for private cover printing functions
	private function writer($value=null) {
		if($value===null) {
			return $this->module_writer;
		}
		$this->module_writer=$value;
		return $this;
	}


	// set status if covers are printed
	public function handle_activation($value=null) {
		if($value===null) {
			return $this->module_activation;
		}
		if($value===true) {
			$this->module_activation=true;
		}
		else {
			$this->module_activation=false;
		}
		return $this;
	}

	// function short calls of handle_print_cover
	public function enable() {
		return $this->handle_activation(true);
	}

	public function disable() {
		return $this->handle_activation(false);
	}


	/*** print nice rack covers/fronts -- be an artist :) ***/
	// dispatch unit cover printing
	public function print_unit_cover($type, $unit_position_top, $unit_position_left, $unit_height, $unit_width) {
		$type='print_unit_cover_'.$type;
		if(method_exists($this, $type)) {
			$this->writer()->SetLineWidth(0.1);
			$this->$type($unit_position_top, $unit_position_left, $unit_height, $unit_width);
		}
		return $this;
	}

	private function print_unit_cover_cover($unit_position_top, $unit_position_left, $unit_height, $unit_width) {
		$this->writer()->Line($unit_position_left, $unit_position_top, $unit_position_left+$unit_width, $unit_position_top+$unit_height);
		$this->writer()->Line($unit_position_left+$unit_width, $unit_position_top, $unit_position_left, $unit_position_top+$unit_height);
	}

	private function print_unit_cover_server($unit_position_top, $unit_position_left, $unit_height, $unit_width) {
		$this->writer()->Line($unit_position_left*1.05, $unit_position_top+$unit_height*0.75/3, $unit_position_left*1.05+$unit_width*0.4, $unit_position_top+$unit_height*0.75/3);
		$this->writer()->Line($unit_position_left*1.05, $unit_position_top+$unit_height*0.75/3*2, $unit_position_left*1.05+$unit_width*0.4, $unit_position_top+$unit_height*0.75/3*2);
		$this->writer()->Line($unit_position_left*1.05, $unit_position_top+$unit_height*0.75, $unit_position_left*1.05+$unit_width*0.4, $unit_position_top+$unit_height*0.75);
	}

	private function print_unit_cover_san() {
	}

}

?>
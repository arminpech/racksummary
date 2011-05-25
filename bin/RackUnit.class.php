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


class RackUnit {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	private $name=null;
	private $rack=null;
	private $type=null;
	private $site=null;
	private $height=null;
	private $position=null;
	private $customer=null;
	private $color=array(null, null, null);


	/*** magic functions ***/
	public function __construct($name=null, $rack=null, $type=null, $site=null, $height=null, $position=null, $customer=null, $color=null) {
		$this->handle_name($name);
		$this->handle_rack($rack);
		$this->handle_type($type);
		$this->handle_site($site);
		$this->handle_height($height);
		$this->handle_position($position);
		$this->handle_customer($customer);
		$this->handle_color($color[0], $color[1], $color[2]);
		return $this;
	}

	public function __destruct() {
	}


	/*** handler functions ***/
	public function handle_name($value=null) {
		if($value!==null) {
			$this->name=$value;
			return $this;
		}
		return $this->name;
	}

	public function handle_rack($value=null) {
		if($value!==null) {
			$this->rack=$value;
			return $this;
		}
		return $this->rack;
	}

	public function handle_type($value=null) {
		if($value!==null) {
			$this->type=$value;
			return $this;
		}
		return $this->type;
	}

	public function handle_site($value=null) {
		if($value!==null) {
			$this->site=$value;
			return $this;
		}
		return $this->site;
	}

	public function handle_height($value=null) {
		if($value!==null) {
			$this->height=$value;
			return $this;
		}
		return $this->height;
	}

	public function handle_position($value=null) {
		if($value!==null) {
			$this->position=$value;
			return $this;
		}
		return $this->position;
	}

	public function handle_customer($value=null) {
		if($value!==null) {
			$this->customer=$value;
			return $this;
		}
		return $this->customer;
	}

	public function handle_color($red=null, $green=null, $blue=null) {
		if($red!==null) {
			$this->handle_color_red($red);
			if($green!==null) {
				$this->handle_color_green($green);
			}
			if($blue!==null) {
				$this->handle_color_blue($blue);
			}
			return $this;
		}
		return $this->color;
	}

	public function handle_color_red($value=null) {
		if($value!==null) {
			$this->color[0]=$value;
			return $this;
		}
		return $this->color[0];
	}

	public function handle_color_green($value=null) {
		if($value!==null) {
			$this->color[1]=$value;
			return $this;
		}
		return $this->color[1];
	}

	public function handle_color_blue($value=null) {
		if($value!==null) {
			$this->color[2]=$value;
			return $this;
		}
		return $this->color[2];
	}
}

?>
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
 */

// load interface to use
require_once('RackCollectorProviderInterface.interface.php');

// load global used utils
require_once(dirname(__FILE__).'/../../RackUtils.class.php');

// load excel reader API (http://code.google.com/p/php-excel-reader/)
// License: http://www.opensource.org/licenses/mit-license.php
require_once('php-excel-reader/excel_reader2.php');

// load sub DAO excel worksheet reader
require_once('ExcelWorksheet.class.php');


class RackCollectorExcel extends RackUtils implements RackCollectorProviderInterface {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** excel source control attributes ***/
	// array('<<file_name>>'=><<Spreadsheet_Excel_Reader instance>>)
	private $excel_files=array();
	// array('<<sheet_name>>'=><<file_name>>);
	private $excel_worksheets=array();

	
	/*** magic functions ***/
	public function __construct($verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
	}

	public function __destruct() {
	}


	/*** excel file handler functions ***/
	private function get_excel_file($file=null) {
		if($file===null) {
			return $this->excel_files;
		}
		if(!isset($this->excel_files[$file])) {
			// used by other functions - must not exit program!
			return false;
		}
		return $this->excel_files[$file];
	}

	private function add_excel_file($file=null) {
		if(!$this->get_excel_file($file)) {
			$handler=new Spreadsheet_Excel_Reader($this->handle_file($file));
			if(!$handler instanceof Spreadsheet_Excel_Reader) {
				$this->err_exit(120, 'excel reader api "Spreadsheet_Excel_Reader" could not be loaded');
			}
			$this->excel_files+=array($file=>$handler);
		}
		return $this;
	}

	/*** unused, because oversized for this application usages ***/
	/*** if you are short of RAM (Oo), feel free and implement it! ***/
	private function delete_excel_file($file, $cascade=false) {
		// TODO: implement function
		// reserved error codes: 121 - 124
		// * this function should be called by delete_excel_worksheet()
		// * check if another worksheet is using this file handler before removal
		// (or ignore if $cascade===true - in this case, delete all worksheets which are using this file)
		return $this;
	}


	/*** excel worksheet handler functions ***/
	public function get_excel_worksheet($sheet=null) {
		if($sheet===null) {
			return $this->excel_worksheets;
		}
		if(is_array($sheet)) {
			if(!count($sheet)>0) {
				$this->err_exit(125, 'function expectes array with at least one element');
			}
			$return=array();
			foreach($sheet as $current_sheet) {
				$return+=array($current_sheet=>$this->get_excel_worksheet($current_sheet));
			}
			return $return;
		}
		if(!isset($this->excel_worksheets[$sheet])) {
			$this->err_exit(126, 'worksheet "'.$sheet.'" is not available');
		}
		return $this->excel_worksheets[$sheet];
	}

	public function add_excel_worksheet($sheet=null, $file=null) {
		// check given parameter
		if($sheet!==null && !strlen($sheet)>0) {
			$this->err_exit(127, 'no sheet name found');
		}
		if(is_array($sheet) && count($sheet)<1) {
			$this->err_exit(131, 'array with sheet names has no elements');
		}
		// prepare sheets and files
		if($file===null) {
			$file=$sheet;
			// for check below
			$sheet=null;
		}
		$this->add_excel_file($file);
		if($sheet===null) {
			$sheet=array();
			foreach($this->get_excel_file($file)->boundsheets as $sheet_data) {
				if(strlen($sheet_data['name'])>0) {
					array_push($sheet, $sheet_data['name']);
				}
			}
		}
		elseif(!is_array($sheet)) {
			$sheet=array($sheet);
		}
		// create sheet handler with relation to file
		foreach($sheet as $current_sheet) {
			if(strlen($current_sheet)<1) {
				$this->err_exit(129, 'sheet name with zero string found');
			}
			$sheet_handler=new ExcelWorksheet($current_sheet, $this->get_excel_file($file), $this->handle_verbose());
			if(!$sheet_handler instanceof ExcelWorksheet) {
				$this->err_exit(130, 'DTO ExcelWorksheet for sheet "'.$current_sheet.'" could not be created');
			}
			$this->excel_worksheets+=array($current_sheet=>$sheet_handler);
		}
		return $this;
	}

	public function delete_excel_worksheet($sheet=null) {
		// TODO: implement delete_excel_file()
		//$file=$this->excel_worksheets[$sheet];
		unset($this->excel_worksheets[$sheet]);
		// TODO: implement delete_excel_file()
		//$this->delete_excel_file($file);
		return $this;
	}


	/*** excel source file handler functions ***/
	public function handle_excel_columns($name=null, $rack=null, $type=null, $site=null, $height=null, $position=null, $customer=null, $color=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_columns($name, $rack, $type, $site, $height, $position, $customer, $color);
		}
		return $this;
	}

	public function handle_excel_name_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_name_column($value);
		}
		return $this;
	}

	public function handle_excel_rack_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_rack_column($value);
		}
		return $this;
	}

	public function handle_excel_type_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_type_column($value);
		}
		return $this;
	}

	public function handle_excel_site_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_site_column($value);
		}
		return $this;
	}

	public function handle_excel_height_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_height_column($value);
		}
		return $this;
	}

	public function handle_excel_position_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_position_column($value);
		}
		return $this;
	}

	public function handle_excel_customer_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_customer_column($value);
		}
		return $this;
	}

	public function handle_excel_color_column($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_color_column($value);
		}
		return $this;
	}

	public function handle_excel_name_prefix($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_name_prefix($value);
		}
		return $this;
	}

	public function handle_excel_process_colors($value=null, $sheets=null) {
		foreach($this->get_excel_worksheet($sheets) as $sheet) {
			$sheet->handle_excel_process_colors($value);
		}
		return $this;
	}


	/*** data output functions ***/
	// get all available units of all worksheets
	public function handle_units() {
		$results=array();
		foreach($this->get_excel_worksheet() as $sheet) {
			$results=array_merge($results, $sheet->handle_units());
		}
		return $results;
	}

	// get unit by specific name
	public function handle_unit_by_name($value) {
		$results=array();
		foreach($this->get_excel_worksheet() as $sheet) {
			$results=array_merge($results, $sheet->handle_unit_by_name($value));
		}
		return $results;
	}

	// get all units of a specific rack
	public function handle_units_by_rack($value) {
		$results=array();
		foreach($this->get_excel_worksheet() as $sheet) {
			$results=array_merge($results, $sheet->handle_units_by_rack($value));
		}
		return $results;
	}

	// get all units of a specific customer
	public function handle_units_by_customer($value) {
		$results=array();
		foreach($this->get_excel_worksheet() as $sheet) {
			$results=array_merge($results, $sheet->handle_units_by_customer($value));
		}
		return $results;
	}

	// get all units of a specific customer
	public function handle_units_by_type($value) {
		$results=array();
		foreach($this->get_excel_worksheet() as $sheet) {
			$results=array_merge($results, $sheet->handle_units_by_type($value));
		}
		return $results;
	}
}

?>
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
 * Last Update: 2011-03-07
 *
 * Website: http://projects.arminpech.de/racksummary/
 *
 *
 * TODO1: implement add/delete/get_excluce/include_option(<<column (int (index))>>, <<content (String (prefix/regexp))>>)
 */

// load global used utils
require_once(dirname(__FILE__).'/../../RackUtils.class.php');

// load rack unit DTO
require_once(dirname(__FILE__).'/../../RackUnit.class.php');


class ExcelWorksheet extends RackUtils {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** excel data attributes ***/
	private $excel_reader=null;
	private $excel_worksheet_index=null;
	private $excel_name_column=null;
	private $excel_rack_column=null;
	private $excel_type_column=null;
	private $excel_site_column=null;
	private $excel_height_column=null;
	private $excel_position_column=null;
	private $excel_customer_column=null;
	private $excel_color_column=null;
	private $excel_name_prefix=null;
	private $excel_process_colors=false;


	/*** magic functions ***/
	public function __construct($sheet, $reader, $verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
		// set excel reader (before setting worksheet! -- see handle_excel_worksheet_index())
		$this->handle_excel_reader($reader);
		// set worksheet index
		if(!strlen($sheet)>0) {
			$this->err_exit(140, 'worksheet "'.$sheet.'" was not found');
		}
		$this->handle_excel_worksheet_index($sheet);
	}

	public function __destruct() {
	}


	/*** handler functions ***/
	// check/handle excel reader api
	private function handle_excel_reader($value=null) {
		if($value!==null) {
			if(!$value instanceof Spreadsheet_Excel_Reader) {
				$this->err_exit(141, 'excel reader API "Spreadsheet_Excel_Reader" could not be found');
			}
			$this->excel_reader=$value;
			return $this;
		}
		return $this->excel_reader;
	}

	// handle current worksheet and corresponding index number
	private function handle_excel_worksheet_index($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(142, 'no worksheet index name or number found');
			}
			if(is_integer($value)) {
				$this->excel_worksheet_index=$value;
			}
			else {
				foreach($this->handle_excel_reader()->boundsheets as $sheet_key=>$sheet_data) {
					if($sheet_data['name']==$value) {
						$this->excel_worksheet_index=$sheet_key;
						break;
					}
				}
			}
			if($this->excel_worksheet_index===null || !((int)$this->excel_worksheet_index>=0)) {
				$this->err_exit(143, 'worksheet index could not be calculated');
			}
			return $this;
		}
		return $this->excel_worksheet_index;
	}

	/* not finished yet
	private function handle_excel_column_index($value) {
		if(!strlen($value)>0) {
			$this->err_exit(144, 'function expects a string with length>0');
		}
		if(is_integer($value)) {
			return $value;
		}
		else {
			$this->handle_excel_reader()->val(1, $this->handle_excel_name_column(), $this->handle_excel_worksheet_index());
		}
	}*/


	/*** excel source file handler functions ***/
	// set all available columns
	public function handle_excel_columns($name=null, $rack=null, $type=null, $site=null, $height=null, $position=null, $customer=null, $color=null) {
		$this->handle_excel_name_column($name);
		$this->handle_excel_rack_column($rack);
		$this->handle_excel_type_column($type);
		$this->handle_excel_site_column($site);
		$this->handle_excel_height_column($height);
		$this->handle_excel_position_column($position);
		$this->handle_excel_customer_column($customer);
		$this->handle_excel_color_column($color);
		return $this;
	}

	public function handle_excel_name_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(144, 'no key for name column found');
			}
			$this->excel_name_column=$value;
			return $this;
		}
		return $this->excel_name_column;
	}

	public function handle_excel_rack_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(145, 'no key for rack column found');
			}
			$this->excel_rack_column=$value;
			return $this;
		}
		return $this->excel_rack_column;
	}

	public function handle_excel_type_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(146, 'no key for type column found');
			}
			$this->excel_type_column=$value;
			return $this;
		}
		return $this->excel_type_column;
	}

	public function handle_excel_site_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(147, 'no key for site column found');
			}
			$this->excel_site_column=$value;
			return $this;
		}
		return $this->excel_site_column;
	}

	public function handle_excel_height_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(148, 'no key for height column found');
			}
			$this->excel_height_column=$value;
			return $this;
		}
		return $this->excel_height_column;
	}

	public function handle_excel_position_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(149, 'no key for position column found');
			}
			$this->excel_position_column=$value;
			return $this;
		}
		return $this->excel_position_column;
	}

	public function handle_excel_customer_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(150, 'no key for customer column found');
			}
			$this->excel_customer_column=$value;
			return $this;
		}
		return $this->excel_customer_column;
	}

	public function handle_excel_color_column($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(151, 'no key for color column found');
			}
			$this->excel_color_column=$value;
			return $this;
		}
		return $this->excel_color_column;
	}

	public function handle_excel_name_prefix($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(152, 'no string for name prefix found');
			}
			$this->excel_name_prefix=$value;
			return $this;
		}
		return $this->excel_name_prefix;
	}

	public function handle_excel_process_colors($value=null) {
		if($value!==null) {
			if($value===true) {
				$this->excel_process_colors=true;
			}
			else {
				$this->excel_process_colors=false;
			}
			return $this;
		}
		return $this->excel_process_colors;
	}


	/*** data output functions ***/
	private function get_unit($row) {
		$unit_name=$this->handle_excel_reader()->val($row, $this->handle_excel_name_column(), $this->handle_excel_worksheet_index());
		if(strlen($unit_name)>0) {
			$check_unit_prefix=true;
			$name_prefix=$this->handle_excel_name_prefix();
			if(strlen($name_prefix)>0) {
				// check with prefix
				if($name_prefix[0]=='/' && $name_prefix[strlen($name_prefix)-1]=='/') {
					if(!preg_match($name_prefix, $unit_name)) {
						$check_unit_prefix=false;
					}
				}
				// normal string comparation
				else {
					if(substr($unit_name, 0, strlen($name_prefix))!=$name_prefix) {
						$check_unit_prefix=false;
					}
				}
			}
			if($check_unit_prefix) {
				$unit=new RackUnit(
					$unit_name,
					$this->handle_excel_reader()->val($row, $this->handle_excel_rack_column(), $this->handle_excel_worksheet_index()),
					$this->handle_excel_reader()->val($row, $this->handle_excel_type_column(), $this->handle_excel_worksheet_index()),
					$this->handle_excel_reader()->val($row, $this->handle_excel_site_column(), $this->handle_excel_worksheet_index()),
					$this->handle_excel_reader()->val($row, $this->handle_excel_height_column(), $this->handle_excel_worksheet_index()),
					$this->handle_excel_reader()->val($row, $this->handle_excel_position_column(), $this->handle_excel_worksheet_index()),
					$this->handle_excel_reader()->val($row, $this->handle_excel_customer_column(), $this->handle_excel_worksheet_index())
				);
				if($this->handle_excel_process_colors()) {
					$color=$this->handle_excel_reader()->bgColor($row, $this->handle_excel_name_column(), $this->handle_excel_worksheet_index());
					if(is_integer($color) && $color>=0) {
						$color=(String)$this->handle_excel_reader()->colors[$color];
						if(strlen($color)==7) {
							$unit->handle_color(hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
						}
					}
				}
				return $unit;
			}
		}
	}

	// get all available units of worksheet
	public function handle_units() {
		$units=array();
		for($i=2; $i<=$this->handle_excel_reader()->rowcount($this->handle_excel_worksheet_index()); $i++) {
			$unit=$this->get_unit($i);
			if($unit instanceof RackUnit) {
				array_push($units, $unit);
			}
		}
		return $units;
	}

	// get unit by specific name
	public function handle_unit_by_name($value) {
		for($i=2; $i<=$this->handle_excel_reader()->rowcount($this->handle_excel_worksheet_index()); $i++) {
			if($this->handle_excel_reader()->val($i, $this->handle_excel_name_column(), $this->handle_excel_worksheet_index())==$value) {
				$unit=$this->get_unit($i);
				if($unit instanceof RackUnit) {
					return $unit;
				}
			}
		}
	}

	// get all units of a specific rack
	public function handle_units_by_rack($value) {
		$units=array();
		for($i=2; $i<=$this->handle_excel_reader()->rowcount($this->handle_excel_worksheet_index()); $i++) {
			if($this->handle_excel_reader()->val($i, $this->handle_excel_rack_column(), $this->handle_excel_worksheet_index())==$value) {
				$unit=$this->get_unit($i);
				if($unit instanceof RackUnit) {
					array_push($units, $unit);
				}
			}
		}
		return $units;
	}

	// get all units of a specific customer
	public function handle_units_by_customer($value) {
		$units=array();
		for($i=2; $i<=$this->handle_excel_reader()->rowcount($this->handle_excel_worksheet_index()); $i++) {
			if($this->handle_excel_reader()->val($i, $this->handle_excel_customer_column(), $this->handle_excel_worksheet_index())==$value) {
				$unit=$this->get_unit($i);
				if($unit instanceof RackUnit) {
					array_push($units, $unit);
				}
			}
		}
		return $units;
	}

	// get all units by type
	public function handle_units_by_type($value) {
		$units=array();
		for($i=2; $i<=$this->handle_excel_reader()->rowcount($this->handle_excel_worksheet_index()); $i++) {
			if($this->handle_excel_reader()->val($i, $this->handle_excel_type_column(), $this->handle_excel_worksheet_index())==$value) {
				$unit=$this->get_unit($i);
				if($unit instanceof RackUnit) {
					array_push($units, $unit);
				}
			}
		}
		return $units;
	}
}

?>
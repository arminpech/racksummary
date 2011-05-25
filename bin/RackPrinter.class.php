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
 * Version: 2011-05-25-alpha
 * Last Update: 2011-05-25
 *
 * Website: http://projects.arminpech.de/racksummary/
 *
 *
 * TODO1: tune algorithm for scaling racks with wide unit descriptions
 * TODO2: setup some customization functions (height types)
 * TODO3: implement a real module concept
 * TODO4: full UTF-8 support
 * TODO5: write unit overlapping detection function
 * TODO6: check if font family is available
 */

// set include path to applications base dir, means ../
set_include_path('..'.PATH_SEPARATOR.get_include_path());

// load global used util functions
require_once('RackUtils.class.php');

// load pdf creator api (http://fpdf.org/)
// License: free / there are no usage restrictions -- great!
require_once('fpdf/fpdf.php');


class RackPrinter extends RackUtils {
	/*** !!! DO NOT CHANGE THESE CLASS ATTRIBUTES OR FUNCTIONS BELOW !!! ***/
	/*** program control attributes ***/
	// application version number -- should not be set on your own
	private $program_version='2011-05-25-alpha';
	// if you want to get the output automatically
	private $program_auto_output=true;
	// fpdf writer api holder
	private $program_writer=null;
	// module memory space
	private $program_modules=array();
	// min. font size
	private $program_min_font_size=4;
	// scaling unit for inch to mm
	private $program_inch_mm=25.4;
	// scaling unit for pt to mm
	private $program_pt_mm=0.3527;

	/*** internal & mostly static attributes ***/
	// available height formats & types for height parsing function
	// array('<<height type>>'=><<height in mounting holes>>)
	// he=Hoeheneinheiten, u=unit, ru=rack unit; be=Befestigungseinheiten, mh=mounting holes
	// ONLY THESE TYPES/THIS VARIBALE CAN BE CHANGED IF NEEDED
	private $program_available_height_types=array('he'=>3, 'u'=>3, 'ru'=>3, 'be'=>1, 'mh'=>1);
	// dynamically on interpretation time set regular expression for height parsing function (see constructor)
	// you must not set this attribute manually!
	private $program_regexp_height_types='';
	// available output formats, values for dynamically pdf scaling
	private $program_output_scalar=array('a5'=>0.5, 'a4'=>1, 'a3'=>2);

	/*** rack and unit informations ***/
	// Name or identifier of the rack
	private $rack_name=null;
	// Description of rack
	private $rack_description=null;
	// value in HE or BE
	private $rack_height=null;
	// rack height text
	private $rack_height_description='rack units';
	// value in inch (most times 19)
	private $rack_width=null;
	// location where to find the rack
	private $rack_location=null;
	// rack site descriptions
	private $rack_front_description='front';
	private $rack_back_description='back';
	// rack site identifier
	private $rack_front_identifier='front';
	private $rack_back_identifier='back';
	// list of units/systems placed in this rack
	private $rack_units=array();

	/*** PDF meta information ***/
	// PDF author
	private $pdf_author=null;
	// PDF  title
	private $pdf_title=null;
	// PDF subject
	private $pdf_subject=null;
	// PDF keywords
	private $pdf_keywords=null;
	// PDF creator -- automatically set by this application;
	// you should not chance this
	private $pdf_creator=null;

	/*** pdf decoration ***/
	// margins of PDF in mm
	private $pdf_margins=null;
	// path to header image displayed in right upper corner
	private $pdf_header_image=null;
	// search in font path
	private $pdf_font_path=null;
	// used font family
	private $pdf_font_family='Arial';
	// default font size
	private $pdf_font_size=12;
	// attribute for scaling racks and units from inch to mm
	private $pdf_rack_scalar=1;
	// fixed width for unit description -- numeric value means percent
	private $pdf_rack_description_width=null;
	// status attribute for displaying hole count or not
	private $pdf_display_hole_count=true;
	// status if you like to display last update string
	private $pdf_display_last_update=true;
	// customized last update prefix string
	private $pdf_last_update_string='Last update';
	// status varibale, if you want to display the creation time (in hh:mm format) of PDF
	private $pdf_display_last_update_time=false;

	/*** output options ***/
	// path to output file
	private $output_file=null;
	// selected output format: a4 etc.
	private $output_format=null;
	// destination where to send the document
	private $output_destination='F';


	/***** magic functions *****/
	public function __construct($verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
		// set pdf creator tag to program version
		$this->handle_pdf_creator('RackSummary '.$this->handle_version());
		// generate regexp of available height types for height parsing function parse_height()
		$this->program_regexp_height_types='(';
		foreach(array_keys($this->program_available_height_types) as $type) {
			$this->program_regexp_height_types.=$type.'|';
		}
		$this->program_regexp_height_types[strlen($this->program_regexp_height_types)-1]=')';
	}

	public function __destruct() {
		// only print rack if no error was detected and auto output is set to true
		if($this->handle_exit_code()===0) {
			if($this->program_auto_output===true) {
				$this->print_rack();
			}
		}
	}


	/***** local used functions *****/
	// *get* my version number
	public function handle_version() {
		return $this->program_version;
	}

	// get or create pdf writer object
	private function writer() {
		if(!$this->program_writer instanceof FPDF) {
			$this->program_writer=new FPDF('P', 'mm', strtoupper($this->handle_output_format()));
			if(!$this->program_writer instanceof FPDF) {
				$this->err_exit(20, 'pdf writer class FPDF could not be created');
			}
			$this->program_writer->SetDisplayMode('real', 'single');
		}
		return $this->program_writer;
	}

	// internal module handler
	private function handle_module($module=null) {
		$module=(String)$module;
		if(strlen($module)<1) {
			$this->err_exit(53, 'no module specified');
		}
		if(isset($this->program_modules[$module]) && $this->program_modules[$module] instanceof $module) {
			return $this->program_modules[$module];
		}
		require_once('modules/printer/'.$module.'.class.php');
		$this->program_modules[$module]=new $module($this->writer(), $this->handle_verbose());
		if(!isset($this->program_modules[$module]) || !($this->program_modules[$module] instanceof $module)) {
			$this->err_exit(54, 'object of module "'.$module.'" could not be created');
		}
		return $this->program_modules[$module];
	}

	// public module handler/interface
	public function module($module) {
		return $this->handle_module($module);
	}

	// parse height into integer, means as BE/MH
	private function parse_height($height) {
		if(strlen($height)<1) {
			$this->err_exit(21, 'wrong height found: "'.$height.'"');
		}
		if(is_numeric($height) && (int)$height>0) {
			return $height;
		}
		// prepare height string as array with number and unit
		$height=explode(' ', preg_replace('/^([0-9]*)'.$this->program_regexp_height_types.'/', '$1 $2', str_replace(' ', '', $height)));
		// is height type available?
		if(!isset($height[1]) || !isset($this->program_available_height_types[strtolower($height[1])])) {
			$this->err_exit(22, 'extracted height type is not available');
		}
		// calculate height in BE
		$height=(int)$height[0]*$this->program_available_height_types[strtolower($height[1])];
		if($height>0) {
			return $height;
		}
		$this->err_exit(23, 'wrong height calculated: "'.$height.'"');
	}

	// *get* scale for inch to mm
	public function handle_inch_mm() {
		return $this->program_inch_mm;
	}

	// *get* scale for pt to mm
	public function handle_pt_mm() {
		return $this->program_pt_mm;
	}


	/***** attribute handler functions *****/
	/*** rack information ***/
	public function handle_rack_name($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(24, 'no rack name found');
			}
			$this->rack_name=$value;
			return $this;
		}
		return $this->rack_name;
	}

	public function handle_rack_description($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(25, 'no rack description found');
			}
			$this->rack_description=$value;
			return $this;
		}
		return $this->rack_description;
	}

	public function handle_rack_height($value=null) {
		if($value!==null) {
			$this->rack_height=$this->parse_height($value);
			return $this;
		}
		return $this->rack_height;
	}

	public function handle_rack_height_description($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(26, 'no rack height description found');
			}
			$this->rack_height_description=$value;
			return $this;
		}
		return $this->rack_height_description;
	}

	public function handle_rack_width($value=null) {
		if($value!==null) {
			if(!((int)$value>0)) {
				$this->err_exit(27, 'wrong width "'.$value.'"');
			}
			$this->rack_width=$value;
			return $this;
		}
		return $this->rack_width;
	}

	public function handle_rack_location($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(28, 'no location found');
			}
			$this->rack_location=$value;
			return $this;
		}
		return $this->rack_location;
	}

	public function handle_rack_front_description($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(29, 'no rack description found');
			}
			$this->rack_front_description=$value;
			return $this;
		}
		return $this->rack_front_description;
	}

	public function handle_rack_back_description($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(30, 'no rack description found');
			}
			$this->rack_back_description=$value;
			return $this;
		}
		return $this->rack_back_description;
	}

	public function handle_rack_front_identifier($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(51, 'no rack identifier found');
			}
			$this->rack_front_identifier=$value;
			return $this;
		}
		return $this->rack_front_identifier;
	}

	public function handle_rack_back_identifier($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(52, 'no rack identifier found');
			}
			$this->rack_back_identifier=$value;
			return $this;
		}
		return $this->rack_back_identifier;
	}

	/*** PDF information ***/
	public function handle_pdf_author($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(31, 'no pdf author found');
			}
			$this->pdf_author=$value;
			return $this;
		}
		return $this->pdf_author;
	}

	public function handle_pdf_title($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(32, 'no pdf title found');
			}
			$this->pdf_title=$value;
			return $this;
		}
		return $this->pdf_title;
	}

	public function handle_pdf_subject($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(33, 'no pdf subject found');
			}
			$this->pdf_subject=$value;
			return $this;
		}
		return $this->pdf_subject;
	}

	public function handle_pdf_keywords($value=null) {
		if($value!==null) {
			if(!(is_array($value) && count($value)>0)) {
				$this->err_exit(34, 'function expects an array with at least one element');
			}
			$keywords='';
			$count=0;
			foreach($value as $word) {
				if(!strlen($word)>0) {
					$this->err_exit(35, 'no string found in element #'.$count);
				}
				$keywords.=$word.', ';
				$count++;
			}
			$this->pdf_keywords=substr($keywords, 0, -2);
			return $this;
		}
		return $this->pdf_keywords;
	}

	private function handle_pdf_creator($value=null) {
		if($value!==null) {
			if(!strlen($value)>0) {
				$this->err_exit(36, 'no pdf creator found');
			}
			$this->pdf_creator=$value;
			return $this;
		}
		return $this->pdf_creator;
	}

	public function handle_pdf_margins($value=null) {
		if($value!==null) {
			if(!((int)$value>=0)) {
				$this->err_exit(37, 'margins for PDF page must be an integer and greater or equal than 0, but "'.$value.'" found');
			}
			$this->pdf_margins=$value;
			return $this;
		}
		return $this->pdf_margins;
	}

	public function handle_pdf_header_image($value=null) {
		if($value!==null) {
			$this->pdf_header_image=$this->handle_file($value);
			return $this;
		}
		return $this->pdf_header_image;
	}

	public function handle_pdf_font_family($value=null) {
		if($value!==null) {
			// TODO5: check if font family is available
			if(!strlen($value)>0) {
				$this->err_exit(38, 'no font family name found');
			}
			$this->pdf_font_family=$value;
			return $this;
		}
		return $this->pdf_font_family;
	}

	public function handle_pdf_font_size($value=null) {
		if($value!==null) {
			if(!((int)$value>$this->program_min_font_size)) {
				$this->err_exit(39, 'please use a font size greater than '.$this->program_min_font_size);
			}
			$this->pdf_font_size=$value;
			return $this;
		}
		return $this->pdf_font_size;
	}

	private function handle_pdf_rack_scalar($value=null) {
		if($value!==null) {
			$value=(double)$value;
			if(!is_double($value)) {
				$this->err_exit(40, 'wrong size "'.$value.'" for rack scalar found, must be an integer or double value');
			}
			$this->pdf_rack_scalar=$value;
			return $this;
		}
		return $this->pdf_rack_scalar;
	}

	public function handle_pdf_rack_description_width($value=null) {
		if($value!==null) {
			$value=(double)$value;
			if(!is_double($value)) {
				$this->err_exit(41, 'wrong size for rack description width: "'.$value.'", must be an integer or double value');
			}
			$this->pdf_rack_description_width=$value;
			return $this;
		}
		return $this->pdf_rack_description_width;
	}

	public function handle_pdf_display_hole_count($value=null) {
		if($value!==null) {
			if($value) {
				$this->pdf_display_hole_count=true;
			}
			else {
				$this->pdf_display_hole_count=false;
			}
			return $this;
		}
		return $this->pdf_display_hole_count;
	}

	public function handle_pdf_display_last_update($value=null) {
		if($value!==null) {
			if($value) {
				$this->pdf_display_last_update=true;
			}
			$this->pdf_display_last_update=false;
			return $this;
		}
		return $this->pdf_display_last_update;
	}

	public function handle_pdf_last_update_string($value=null) {
		if($value!==null) {
			if(!strlen($value)) {
				$this->err_exit(42, 'wrong string found for last update string: "'.$value.'"');
			}
			$this->pdf_last_update_string=$value;
			return $this;
		}
		return $this->pdf_last_update_string;
	}

	public function handle_pdf_display_last_update_time($value=null) {
		if($value!==null) {
			if($value) {
				$this->pdf_display_last_update_time=true;
			}
			else {
				$this->pdf_display_last_update_time=false;
			}
			return $this;
		}
		return $this->pdf_display_last_update_time;
	}


	/*** output options ***/
	public function handle_output_file($value=null) {
		if($value!==null) {
			$this->output_file=$this->handle_file($value, true);
			return $this;
		}
		return $this->output_file;
	}

	public function handle_output_format($value=null) {
		if($value!==null) {
			if(!array_key_exists(strtolower($value), $this->program_output_scalar)) {
				$this->err_exit(43, 'output format "'.$value.'" is not available');
			}
			$this->output_format=$value;
			return $this;
		}
		return $this->output_format;
	}

	public function handle_output_destination($value=null) {
		if($value===null) {
			return $this->output_destination;
		}
		if($value=='i' || $value=='I' || $value=='inline') {
			$this->output_destination='I';
		}
		elseif($value=='d' || $value=='D' || $value=='download') {
			$this->output_destination='D';
		}
		elseif($value=='f' || $value=='F' || $value=='file') {
			$this->output_destination='F';
		}
		else {
			$this->err_exit(44, 'output destination "'.$value.'" is not supported');
		}
		return $this;
	}

	public function handle_output_destination_inline($value=false) {
		if($value!==true) {
			if($this->handle_output_destination()=='I') {
				return true;
			}
			return false;
		}
		return $this->handle_output_destination('I');
	}

	public function handle_output_destination_download($value=false) {
		if($value!==true) {
			if($this->handle_output_destination()=='D') {
				return true;
			}
			return false;
		}
		return $this->handle_output_destination('D');
	}

	public function handle_output_destination_file($value=false) {
		if($value!==true) {
			if($this->handle_output_destination()=='F') {
				return true;
			}
			return false;
		}
		return $this->handle_output_destination('F');
	}


	/*** other public used functions ***/
	// do no auto output to file when program ends
	public function disable_auto_output() {
		$this->program_auto_output=false;
		return $this;
	}


	/*** unit management for rack sites ***/
	// get an/all added unit/s
	public function get_unit($name=null) {
		if($name===null) {
			return $this->rack_units;
		}
		if(!isset($this->_rack_units[$name])) {
			$this->err_exit(45, 'unit with name "'.$name.'" could not be found');
		}
		return $this->rack_units[$name];
	}

	// add a unit/system to a rack (site)
	public function add_unit($unit) {
		if(is_array($unit)) {
			if(!count($unit)>0) {
				$this->err_exit(46, 'array must have at least one element');
			}
			foreach($unit as $current_unit) {
				$this->add_unit($current_unit);
			}
			return $this;
		}
		if(!$unit instanceof RackUnit) {
			$this->err_exit(47, 'function expects data of object "RackUnit"');
		}
		if(isset($this->rack_units[$unit->handle_name()])) {
			$this->err_exit(48, 'another unit with name "'.$unit->handle_name().'" had already been added to unit dataset');
		}
		$this->rack_units+=array($unit->handle_name()=>$unit);
		return $this;
	}

	// check if unit placed in rack and delete it
	public function delete_unit($name) {
		if(array_key_exists($name, $this->rack_units)) {
			unset($this->rack_units[$name]);
		}
		return $this;
	}


	/*** printer and positioning functions ***/
	// print unit on a rack site
	private function print_unit($rack_margin_top, $rack_margin_left, $unit) {
		// prepare static values
		$unit_position_top=$rack_margin_top+($unit->handle_position()-1)*0.58*$this->handle_pdf_rack_scalar();
		$unit_position_left=$rack_margin_left+0.5851
*$this->handle_pdf_rack_scalar()+0.1;
		$unit_height=$this->parse_height($unit->handle_height())*0.58*$this->handle_pdf_rack_scalar();
		$unit_width=$this->handle_rack_width()*$this->handle_pdf_rack_scalar()-0.26;
		$unit_rack_width=($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar();
		// prepare pdf writer
		$this->writer()->SetLineWidth(0.14);
		$this->writer()->SetDrawColor(0);
		if($unit->handle_color_red()!==null) {
			if($unit->handle_color_green()!==null && $unit->handle_color_blue()!==null) {
				$this->writer()->SetFillColor($unit->handle_color_red(), $unit->handle_color_green(), $unit->handle_color_blue());
			}
			else {
				$this->writer()->SetFillColor($unit->handle_color_red());
			}
		}
		else {
			$this->writer()->SetFillColor(220);
		}
		// print unit name
		$this->writer()->SetFont($this->handle_pdf_font_family(), '', $this->handle_pdf_rack_scalar()*3.1);
		$this->writer()->Text($rack_margin_left-$this->writer()->GetStringWidth($unit->handle_name())-1.3, $unit_position_top+$this->handle_pdf_rack_scalar()*0.352+$unit_height/2, $unit->handle_name());
		// print unit to rack
		$this->writer()->Rect($unit_position_left, $unit_position_top, $unit_width+0.014*$this->handle_pdf_rack_scalar(), $unit_height, 'F');
		$this->writer()->Line($rack_margin_left, $unit_position_top, $rack_margin_left+$unit_rack_width, $unit_position_top);
		$this->writer()->Line($rack_margin_left, $unit_position_top+$unit_height, $rack_margin_left+$unit_rack_width, $unit_position_top+$unit_height);
		// design cover of unit if function is available -- TODO
		if($this->module('RackCoverPrinter')->handle_activation()===true) {
			$this->module('RackCoverPrinter')->print_unit_cover($unit->handle_type(), $unit_position_top, $unit_position_left, $unit_height, $unit_width);
		}
		return $this;
	}


	// print front or back of rack
	private function print_site($description, $margin_top, $margin_left) {
		// print site description/name
		$this->writer()->SetFont($this->handle_pdf_font_family(), '', $this->handle_pdf_font_size());
		$this->writer()->SetX($margin_left);
		$this->writer()->Cell(($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar(), 0, $description, 0, 0, 'C');
		// draw rack
		$this->writer()->SetLineWidth(0.2);
		$this->writer()->Line($margin_left, $margin_top, $margin_left+($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar(), $margin_top);
		$this->writer()->Line($margin_left, $margin_top, $margin_left, $margin_top+$this->handle_rack_height()*$this->handle_pdf_rack_scalar()*0.58);
		$this->writer()->Line($margin_left+0.58*$this->handle_pdf_rack_scalar(), $margin_top, $margin_left+0.58*$this->handle_pdf_rack_scalar(), $margin_top+$this->handle_rack_height()*$this->handle_pdf_rack_scalar()*0.58);
		$this->writer()->Line($margin_left+($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar(), $margin_top, $margin_left+($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar(), $margin_top+$this->handle_rack_height()*$this->handle_pdf_rack_scalar()*0.58);
		$this->writer()->Line($margin_left+($this->handle_rack_width()+0.58)*$this->handle_pdf_rack_scalar(), $margin_top, $margin_left+($this->handle_rack_width()+0.58)*$this->handle_pdf_rack_scalar(), $margin_top+$this->handle_rack_height()*$this->handle_pdf_rack_scalar()*0.58);
		// prepare environment for drawing holes
		$margin_top+=$this->handle_pdf_rack_scalar()*0.165;
		$hole_count=0;
		$this->writer()->SetDrawColor(0);
		$this->writer()->SetFillColor(0);
		$this->writer()->SetLineWidth(0.14);
		// set font size for hole number markers
		$this->writer()->SetFont($this->handle_pdf_font_family(), '', $this->handle_pdf_rack_scalar()*2);
		// draw holes
		while($this->handle_rack_height()>$hole_count) {
			$this->writer()->Rect($margin_left+$this->handle_pdf_rack_scalar()*0.165, $margin_top+$hole_count*0.58*$this->handle_pdf_rack_scalar(), 0.25*$this->handle_pdf_rack_scalar(), 0.25*$this->handle_pdf_rack_scalar(), 'F');
			$this->writer()->Rect($margin_left+($this->handle_rack_width()+0.745)*$this->handle_pdf_rack_scalar(), $margin_top+$hole_count*0.58*$this->handle_pdf_rack_scalar(), 0.25*$this->handle_pdf_rack_scalar(), 0.25*$this->handle_pdf_rack_scalar(), 'F');
			// mark hole with number
			if($this->handle_pdf_display_hole_count() && ($hole_count+1)%5==0) {
				$this->writer()->Text($margin_left+($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar()+0.75, $margin_top+($hole_count+0.67)*0.58*$this->handle_pdf_rack_scalar(), $hole_count+1);
			}
			$hole_count++;
		}
		// draw rack base
		$this->writer()->SetLineWidth(0.2);
		$this->writer()->Rect($margin_left, $margin_top-0.3+$this->handle_rack_height()*$this->handle_pdf_rack_scalar()*0.58, ($this->handle_rack_width()+0.58*2)*$this->handle_pdf_rack_scalar(), $this->handle_pdf_rack_scalar()*2.2);
		// wind back to origin font size
		$this->writer()->SetFont($this->handle_pdf_font_family(), '', $this->handle_pdf_font_size());
		return $this;
	}

	// print rack to PDF
	public function print_rack() {
		// set meta data (in UTF8 -- true)
		$this->writer()->SetAuthor($this->handle_pdf_author(), true);
		$this->writer()->SetTitle($this->handle_pdf_title(), true);
		$this->writer()->SetSubject($this->handle_pdf_subject(), true);
		$this->writer()->SetCreator($this->handle_pdf_creator(), true);
		$this->writer()->SetKeywords($this->handle_pdf_keywords(), true);
		// prepare page
		$this->writer()->SetMargins($this->handle_pdf_margins(), $this->handle_pdf_margins());
		$this->writer()->SetFont($this->handle_pdf_font_family(), 'B', $this->handle_pdf_font_size());
		$this->writer()->AddPage();
		// print header (name, location, image, etc.)
		$this->writer()->SetXY($this->handle_pdf_margins(), $this->handle_pdf_margins());
		$this->writer()->Write(0, $this->handle_rack_name());
		$this->writer()->Ln($this->handle_pdf_font_size()/2);
		$this->writer()->SetFont($this->handle_pdf_font_family(), '', $this->handle_pdf_font_size());
		$this->writer()->Write(0, $this->handle_rack_location());
		$this->writer()->Ln($this->handle_pdf_font_size()/2);
		if(strlen($this->handle_rack_height_description())>0) {
			$this->writer()->Write(0, $this->handle_rack_height_description().': '.$this->handle_rack_height()/3);
		}
		$this->writer()->Ln($this->handle_pdf_font_size()*0.8);
		// print image
		if(strlen($this->handle_pdf_header_image())>0) {
			// php.net: "This function does not require the GD image library." -- yeah :)
			$max_image_height=5*4;
			$image_height=getimagesize($this->handle_pdf_header_image());
			$image_width=$image_height[0]*0.3;
			$image_height=$image_height[1]*0.3;
			if($image_height>$max_image_height) {
				$image_width=$image_width*$max_image_height/$image_height;
				$image_height=$max_image_height;
			}
			$this->writer()->Image($this->handle_pdf_header_image(), (int)$this->writer()->CurPageFormat[0]-(int)$this->writer()->lMargin-$image_width, $this->handle_pdf_margins()*0.8, $image_width, $image_height);
		}
		// print last update = today
		if($this->handle_pdf_display_last_update()) {
			$last_update='d.m.Y';
			if($this->handle_pdf_display_last_update_time()) {
				$last_update.=' (H:i)';
			}
			$this->writer()->Text((int)$this->writer()->lMargin, (int)$this->writer()->CurPageFormat[1]-(int)$this->writer()->tMargin, $this->handle_pdf_last_update_string().': '.date($last_update));
		}
		// calculate description width
		$description_width=0;
		$longest_string='';
		if($this->handle_pdf_rack_description_width()!==null) {
			$description_width=((int)$this->writer()->CurPageFormat[0]-$this->writer()->lMargin*2)*$this->handle_pdf_rack_description_width()/100/2;
		}
		else {
			foreach($this->get_unit() as $unit) {
				$string_width=(int)$this->writer()->GetStringWidth($unit->handle_name());
				if($string_width>$description_width) {
					$description_width=$string_width;
					$longest_string=$unit->handle_name();
				}
			}
			// straighten the description width with 2eo + 1ei a la 1em
			$description_width+=$this->writer()->GetStringWidth('OOi');
			$longest_string.='OOi';
		}
		// set rack/unit scalar (inch -> mm)
		$rack_inch_scalar=($this->writer()->CurPageFormat[1]-$this->handle_pdf_margins()*2-9*$this->handle_pdf_font_size()*$this->handle_pt_mm())/($this->handle_rack_height()+3)*1.72;
		// avoid page rack overflow
		$max_rack_width=$this->writer()->CurPageFormat[0]/2-$this->handle_pdf_margins()-$description_width;
		$rack_width=$this->handle_rack_width()*$rack_inch_scalar;
		if($rack_width>$max_rack_width) {
			$rack_inch_scalar*=$max_rack_width/$rack_width;
			$description_width*=$max_rack_width/$rack_width*0.9;
		}
		else {
			$description_width*=$rack_width/$max_rack_width*0.85;
		}
		$this->handle_pdf_rack_scalar($rack_inch_scalar);
		// set rack positions
		$rack_margin_top=$this->writer()->GetY()+$this->handle_pdf_font_size()/2;
		$rack_front_margin_left=$this->writer()->lMargin+$description_width;
		$rack_back_margin_left=(int)$this->writer()->CurPageFormat[0]/2+$description_width;
		// print rack sites
		$this->print_site($this->handle_rack_front_description(), $rack_margin_top, $rack_front_margin_left);
		$this->print_site($this->handle_rack_back_description(), $rack_margin_top, $rack_back_margin_left);
		// now print the units...
		foreach($this->get_unit() as $unit) {
			// of front site
			if($unit->handle_site()==$this->handle_rack_front_identifier()) {
				$this->print_unit($rack_margin_top, $rack_front_margin_left, $unit);
			}
			// or back site
			elseif($unit->handle_site()==$this->handle_rack_back_identifier()) {
				$this->print_unit($rack_margin_top, $rack_back_margin_left, $unit);
			}
			// oops -- see you next try
			else {
				$this->err_exit(49, 'rack site identifier of unit "'.$unit->handle_name().'" is not correct, expecting "'.$this->handle_rack_front_identifier().'" (front site) or "'.$this->handle_rack_back_identifier().'" (back site) but found "'.$unit->handle_site().'"');
			}
		}
		// output to file
		if($this->handle_output_destination_file() && is_null($this->handle_output_file())) {
			$this->err_exit(50, 'output destination is "file" but no output file was set');
		}
		$this->writer()->Output($this->handle_output_file(), $this->handle_output_destination());
		return $this;
	}
}

?>
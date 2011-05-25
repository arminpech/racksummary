#!/usr/bin/php
<?php
// delete the shebang on top of file to execute this script on a webserver

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
 * Last Update: 2011-05-25
 *
 * Website: http://projects.arminpech.de/racksummary/
 */

// if you use a brwoser to call this script,
// you may want to get the errors in plain text:
//header('Content-Type: text/plain');


// load required classes
// data collector class (abstract)
require_once('bin/RackCollector.class.php');
// PDF output/printer class
require_once('bin/RackPrinter.class.php');

// *** collect some units from an excel file ***
// create collector with Excel source provider and debug level
$rc=new RackCollector('Excel', true);

// gives me the ability to select all units from all worksheets of file 'example.xls'
$rc->add_excel_worksheet('var/example.xls');
// i want to get only/additional the units from sheet 'Sheet1' of file 'example2.xls'
//$rc->add_excel_worksheet('Sheet1', 'var/example2.xls');
// also applicable:)
//$rc->add_excel_worksheet(array('Sheet1', 'MyShEEt2'), 'var/example2.xls');
// set required columns with unit data and activate color processing
$rc->handle_excel_columns(1, 2, 7, 3, 4, 5, 6)->handle_excel_process_colors(true);
// $rc->handle_excel_columns(<<name>>, <<rack>>, <<type>>, <<site>>, <<height>>, <<position>>, <<customer>>, <<color>>);
// select only units which names contain '01' -- this (/<<regexp>>/) is handled as a regexp!
//$rc->handle_excel_name_prefix('/01/');
// and here is an example for a normal name prefix ;)
//$rc->handle_excel_name_prefix('s');


//*** create PDF output with units on the rack ***
// create object with output functions and debug level
$rp=new RackPrinter(true);

// set pdf title
$rp->handle_pdf_title('Test Rack');
// set pdf author
$rp->handle_pdf_author('Armin Pech');
// set pdf subject
$rp->handle_pdf_subject('Test Rack / Babiel GmbH Co-Location');
// add some pdf keywords
$rp->handle_pdf_keywords(array('test', 'rack', 'armin', 'pech', 'babiel'));
// set page margins in mm
$rp->handle_pdf_margins(8);
// description font size in pt
$rp->handle_pdf_font_size(16);
// add header image to upper right corner (automatically scaled by description/image height/width)
$rp->handle_pdf_header_image('var/images/test.jpg');

// set rack name
$rp->handle_rack_name($rp->handle_pdf_title());
// set location of rack
$rp->handle_rack_location('Babiel GmbH Co-Location');
// set height of rack in HE/Units
$rp->handle_rack_height('47he');
// set header height description
$rp->handle_rack_height_description('rack height (in rack units)');
// set rack width in inch
$rp->handle_rack_width(19);
// set description of rack front
$rp->handle_rack_front_description('front site');
// set description of rack back site
$rp->handle_rack_back_description('back site');
// set rack front site identifier string
$rp->handle_rack_front_identifier('front');
// set rack back site identifier string
$rp->handle_rack_front_identifier('front');
// set last update string
$rp->handle_pdf_last_update_string('Date of creation');
// display last update date and time
//$rp->handle_pdf_display_last_update(true);
//$rp->handle_pdf_display_last_update_time(true);

// set output file
$rp->handle_output_file('var/rack.pdf');
// output as stream for browser PDF plugin
//$rp->handle_output_destination_inline(true);
// output format is DINA4
$rp->handle_output_format('a4');

//*** here meets your data collector the rack printer and gives him an array with units to print ***
// get all units and print them
//$rp->add_unit($rc->provider()->handle_units());
// get only units from rack "test" and print them
$rp->add_unit($rc->handle_units_by_rack('test'));

//*** available printer modules ***
// enable cover printer module
$rp->module('RackCoverPrinter')->enable();

?>
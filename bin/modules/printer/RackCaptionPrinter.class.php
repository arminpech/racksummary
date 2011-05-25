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
 *
 * TODO: this class is totally unfinish and needs to be extended
 */

// load global used util functions
require_once(dirname(__FILE__).'/../../RackUtils.class.php');


class RackCaptionPrinter extends RackUtils {
	/*** magic functions ***/
	public function __construct($verbose=false) {
		// set logging options
		$this->handle_verbose($verbose);
	}

	public function __destruct() {
	}
}

?>
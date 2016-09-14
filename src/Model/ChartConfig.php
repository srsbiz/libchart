<?php

/* Libchart - PHP chart library
 * Copyright (C) 2005-2011 Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

namespace Libchart\Model;

/**
 * Configuration attributes of the chart.
 *
 * @author Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
 */
class ChartConfig {

	/**
	 * Use several colors for a single data set chart (as if it was a multiple data set).
	 * 
	 * @var Boolean
	 */
	private $useMultipleColor;

	/**
	 * Show caption on individual data points.
	 * 
	 * @var Boolean
	 */
	private $showPointCaption;

	/**
	 * Sort data points (only pie charts).
	 * 
	 * @var Boolean
	 */
	private $sortDataPoint;

	/**
	 * Creates a new ChartConfig with default options.
	 */
	public function __construct($useMultipleColor = false, $showPointCaption = true, $sortDataPoint = true) {
		$this->useMultipleColor = $useMultipleColor;
		$this->showPointCaption = $showPointCaption;
		$this->sortDataPoint = $sortDataPoint;
	}

	/**
	 * If true the chart will use several colors for a single data set chart
	 * (as if it was a multiple data set).
	 * 
	 * @param boolean $useMultipleColor Use several colors
	 */
	public function setUseMultipleColor($useMultipleColor) {
		$this->useMultipleColor = $useMultipleColor;
		return $this;
	}

	/**
	 * If true the chart will use several colors for a single data set chart
	 * (as if it was a multiple data set).
	 * 
	 * @return boolean Use several colors
	 */
	public function getUseMultipleColor() {
		return $this->useMultipleColor;
	}

	/**
	 * Set the option to show caption on individual data points.
	 * 
	 * @param boolean $showPointCaption Show caption on individual data points
	 */
	public function setShowPointCaption($showPointCaption) {
		$this->showPointCaption = $showPointCaption;
		return $this;
	}

	/**
	 * Get the option to show caption on individual data points.
	 * 
	 * @return boolean Show caption on individual data points
	 */
	public function getShowPointCaption() {
		return $this->showPointCaption;
	}

	/**
	 * Set the option to sort data points (only pie charts).
	 * 
	 * @param boolean $sortDataPoint Sort data points
	 */
	public function setSortDataPoint($sortDataPoint) {
		$this->sortDataPoint = $sortDataPoint;
		return $this;
	}

	/**
	 * Get the option to sort data points (only pie charts).
	 * 
	 * @return boolean Sort data points
	 */
	public function getSortDataPoint() {
		return $this->sortDataPoint;
	}

}

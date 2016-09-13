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

namespace Libchart\View\Plot;

/**
 * The plot holds graphical attributes, and is responsible for computing the layout of the graph.
 * The layout is quite simple right now, with 4 areas laid out like that:
 * (of course this is subject to change in the future).
 *
 * output area------------------------------------------------|
 * |  (outer padding)                                         |
 * |  image area--------------------------------------------| |
 * |  | (title padding)                                     | |
 * |  | title area----------------------------------------| | |
 * |  | |-------------------------------------------------| | |
 * |  |                                                     | |
 * |  | (graph padding)              (caption padding)      | |
 * |  | graph area----------------|  caption area---------| | |
 * |  | |                         |  |                    | | |
 * |  | |                         |  |                    | | |
 * |  | |                         |  |                    | | |
 * |  | |                         |  |                    | | |
 * |  | |                         |  |                    | | |
 * |  | |-------------------------|  |--------------------| | |
 * |  |                                                     | |
 * |  |-----------------------------------------------------| |
 * |                                                          |
 * |----------------------------------------------------------|
 *
 * All area dimensions are known in advance , and the optional logo is drawn in absolute coordinates.
 *
 * @author Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
 * Created on 27 july 2007
 */
class Plot {

	const IMAGE_PRINT_BORDER_NONE = 0;
	const IMAGE_PRINT_BORDER_OUTPUT_AREA = 1;
	const IMAGE_PRINT_BORDER_IMAGE_AREA = 2;
	
	// Style properties
	protected $title;

	/**
	 * Location of the logo. Can be overriden to your personalized logo.
	 */
	protected $logoFileName;

	/**
	 * Outer area, whose dimension is the same as the PNG returned.
	 */
	protected $outputArea;

	/**
	 * Outer padding surrounding the whole image, everything outside is blank.
	 */
	protected $outerPadding;

	/**
	 * Coordinates of the area inside the outer padding.
	 */
	protected $imageArea;

	/**
	 * Fixed title height in pixels.
	 */
	protected $titleHeight;

	/**
	 * Padding of the title area.
	 */
	protected $titlePadding;

	/**
	 *  Coordinates of the title area.
	 */
	protected $titleArea;

	/**
	 * True if the plot has a caption.
	 */
	protected $hasCaption;

	/**
	 * Ratio of graph/caption in width.
	 */
	protected $graphCaptionRatio;

	/**
	 * Padding of the graph area.
	 */
	protected $graphPadding;

	/**
	 * Coordinates of the graph area.
	 * @var \Libchart\View\Primitive\Rectangle
	 */
	protected $graphArea;

	/**
	 * Padding of the caption area.
	 */
	protected $captionPadding;

	/**
	 * Coordinates of the caption area.
	 * @var \Libchart\View\Primitive\Rectangle
	 */
	protected $captionArea;

	/**
	 * Text writer.
	 * @var \Libchart\View\Text\Text
	 */
	protected $text;

	/**
	 * Color palette.
	 * @var \Libchart\View\Color\Palette
	 */
	protected $palette;

	/**
	 * Label generator.
	 * @var \Libchart\View\Label\LabelGenerator
	 */
	protected $labelGenerator;

	/**
	 * GD image
	 * @var resource GD
	 */
	protected $img;
	
	/**
	 * Determine if 
	 * @var int 
	 */
	protected $printImageBorder = 0;
	
	/**
	 * Border color
	 * @var int[3] R,G,B (0-255)
	 */
	protected $imageBorderColor = [];

	/**
	 * Drawing primitives
	 */
	protected $primitive;
	protected $backGroundColor;
	protected $textColor;

	/**
	 * Constructor of Plot.
	 *
	 * @param integer width of the image
	 * @param integer height of the image
	 */
	public function __construct($width = 600, $height = 300, $outerPadding = 5, $titlePadding = 5, $titleHeight = 26, 
		$graphCaptionRatio = 0.5, $graphPadding = 50, $captionPadding = 15) {
		$this->width = $width;
		$this->height = $height;

		$this->text = new \Libchart\View\Text\Text();
		$this->palette = new \Libchart\View\Color\Palette();
		$this->labelGenerator = new \Libchart\View\Label\DefaultLabelGenerator();

		// Default layout
		$this->outputArea = new \Libchart\View\Primitive\Rectangle(0, 0, $width - 1, $height - 1);
		$this->outerPadding = new \Libchart\View\Primitive\Padding($outerPadding);
		$this->titleHeight = $titleHeight;
		$this->titlePadding = new \Libchart\View\Primitive\Padding($titlePadding);
		$this->hasCaption = false;
		$this->graphCaptionRatio = $graphCaptionRatio;
		$this->graphPadding = new \Libchart\View\Primitive\Padding($graphPadding);
		$this->captionPadding = new \Libchart\View\Primitive\Padding($captionPadding);
	}

	/**
	 * Compute the area inside the outer padding (outside is white).
	 */
	private function computeImageArea() {
		$this->imageArea = $this->outputArea->getPaddedRectangle($this->outerPadding);
	}

	/**
	 * Compute the title area.
	 */
	private function computeTitleArea() {
		$titleUnpaddedBottom = $this->imageArea->y1 + $this->titleHeight + $this->titlePadding->top + $this->titlePadding->bottom;
		$titleArea = new \Libchart\View\Primitive\Rectangle(
				$this->imageArea->x1, $this->imageArea->y1, $this->imageArea->x2, $titleUnpaddedBottom - 1
		);
		$this->titleArea = $titleArea->getPaddedRectangle($this->titlePadding);
	}

	/**
	 * Compute the graph area.
	 */
	private function computeGraphArea() {
		$titleUnpaddedBottom = $this->imageArea->y1 + $this->titleHeight + $this->titlePadding->top + $this->titlePadding->bottom;
		$graphArea = null;
		if ($this->hasCaption) {
			$graphUnpaddedRight = $this->imageArea->x1 + ($this->imageArea->x2 - $this->imageArea->x1) * $this->graphCaptionRatio + $this->graphPadding->left + $this->graphPadding->right;
			$graphArea = new \Libchart\View\Primitive\Rectangle(
					$this->imageArea->x1, $titleUnpaddedBottom, $graphUnpaddedRight - 1, $this->imageArea->y2
			);
		} else {
			$graphArea = new \Libchart\View\Primitive\Rectangle(
					$this->imageArea->x1, $titleUnpaddedBottom, $this->imageArea->x2, $this->imageArea->y2
			);
		}
		$this->graphArea = $graphArea->getPaddedRectangle($this->graphPadding);
	}

	/**
	 * Compute the caption area.
	 */
	private function computeCaptionArea() {
		$graphUnpaddedRight = $this->imageArea->x1 + ($this->imageArea->x2 - $this->imageArea->x1) * $this->graphCaptionRatio + $this->graphPadding->left + $this->graphPadding->right;
		$titleUnpaddedBottom = $this->imageArea->y1 + $this->titleHeight + $this->titlePadding->top + $this->titlePadding->bottom;
		$captionArea = new \Libchart\View\Primitive\Rectangle(
				$graphUnpaddedRight, $titleUnpaddedBottom, $this->imageArea->x2, $this->imageArea->y2
		);
		$this->captionArea = $captionArea->getPaddedRectangle($this->captionPadding);
	}

	/**
	 * Compute the layout of all areas of the graph.
	 */
	public function computeLayout() {
		$this->computeImageArea();
		$this->computeTitleArea();
		$this->computeGraphArea();
		if ($this->hasCaption) {
			$this->computeCaptionArea();
		}
	}

	/**
	 * Creates and initialize the image.
	 */
	public function createImage() {
		$this->img = imagecreatetruecolor($this->width, $this->height);

		$this->primitive = new \Libchart\View\Primitive\Primitive($this->img);

		$this->backGroundColor = new \Libchart\View\Color\Color(255, 255, 255);
		$this->textColor = new \Libchart\View\Color\Color(0, 0, 0);

		// White background
		imagefilledrectangle($this->img, 0, 0, $this->width - 1, $this->height - 1, $this->backGroundColor->getColor($this->img));

		if ($this->printImageBorder) {
			$color = imagecolorallocate($this->img, $this->imageBodrerColor[0], $this->imageBodrerColor[1], $this->imageBodrerColor[2]);

			if ($this->printImageBorder == self::IMAGE_PRINT_BORDER_OUTPUT_AREA) {
				imagerectangle($this->img, 0, 0, $this->width - 1, $this->height - 1, $color);
			} elseif ($this->printImageBorder == self::IMAGE_PRINT_BORDER_IMAGE_AREA) {
				imagerectangle($this->img, $this->imageArea->x1, $this->imageArea->y1, $this->imageArea->x2, $this->imageArea->y2, $color);
			}
		}
	}

	/**
	 * Print the title to the image.
	 */
	public function printTitle() {
		$yCenter = $this->titleArea->y1 + ($this->titleArea->y2 - $this->titleArea->y1) / 2;
		$this->text->printCentered($this->img, $yCenter, $this->textColor, $this->title, $this->text->fontCondensedBold);
	}

	/**
	 * Print the logo image to the image.
	 */
	public function printLogo() {
		if ($this->logoFileName) {
			$logoImage = @imageCreateFromString(file_get_contents($this->logoFileName));

			if ($logoImage) {
				imagecopymerge($this->img, $logoImage, 2 * $this->outerPadding->left, $this->outerPadding->top, 0, 0, imagesx($logoImage), imagesy($logoImage), 100);
			}
		}
	}

	/**
	 * Renders to a file or to standard output.
	 *
	 * @param string $fileName File name (optional)
	 */
	public function render($fileName = null) {		
		imagepng($this->img, $fileName);		
	}

	/**
	 * Sets the title.
	 *
	 * @param string New title
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Sets the logo image file name.
	 *
	 * @param string $logoFileName New logo image file name or null to skip printig logo
	 */
	public function setLogoFileName($logoFileName) {
		$this->logoFileName = $logoFileName;
		return $this;
	}

	/**
	 * Return the GD image.
	 *
	 * @return GD Image
	 */
	public function getImg() {
		return $this->img;
	}

	/**
	 * Return the palette.
	 *
	 * @return \Libchart\View\Color\Palette
	 */
	public function getPalette() {
		return $this->palette;
	}

	/**
	 * Return the text.
	 *
	 * @return \Libchart\View\Text\Text
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Return the primitive.
	 *
	 * @return primitive
	 */
	public function getPrimitive() {
		return $this->primitive;
	}

	/**
	 * Return the outer padding.
	 *
	 * @param integer Outer padding value in pixels
	 */
	public function getOuterPadding() {
		return $this->outerPadding;
	}

	/**
	 * Set the outer padding.
	 *
	 * @param integer Outer padding value in pixels
	 */
	public function setOuterPadding($outerPadding) {
		$this->outerPadding = $outerPadding;
		return $this;
	}

	/**
	 * Return the title height.
	 *
	 * @param integer title height
	 */
	public function setTitleHeight($titleHeight) {
		$this->titleHeight = $titleHeight;
		return $this;
	}

	/**
	 * Return the title padding.
	 *
	 * @param integer title padding
	 */
	public function setTitlePadding($titlePadding) {
		$this->titlePadding = $titlePadding;
		return $this;
	}

	/**
	 * Return the graph padding.
	 *
	 * @param integer graph padding
	 */
	public function setGraphPadding($graphPadding) {
		$this->graphPadding = $graphPadding;
		return $this;
	}

	/**
	 * Set if the graph has a caption.
	 *
	 * @param boolean graph has a caption
	 */
	public function setHasCaption($hasCaption) {
		$this->hasCaption = $hasCaption;
		return $this;
	}

	/**
	 * Set the caption padding.
	 *
	 * @param integer caption padding
	 */
	public function setCaptionPadding($captionPadding) {
		$this->captionPadding = $captionPadding;
		return $this;
	}

	/**
	 * Set the graph/caption ratio.
	 *
	 * @param integer caption padding
	 */
	public function setGraphCaptionRatio($graphCaptionRatio) {
		$this->graphCaptionRatio = $graphCaptionRatio;
		return $this;
	}

	/**
	 * Return the label generator.
	 *
	 * @return \Libchart\View\Label\LabelGenerator Label generator
	 */
	public function getLabelGenerator() {
		return $this->labelGenerator;
	}

	/**
	 * Set the label generator.
	 *
	 * @param \Libchart\View\Label\LabelGenerator $labelGenerator Label generator
	 */
	public function setLabelGenerator($labelGenerator) {
		$this->labelGenerator = $labelGenerator;
	}

	/**
	 * Return the graph area.
	 *
	 * @return \Libchart\View\Primitive\Rectangle graph area
	 */
	public function getGraphArea() {
		return $this->graphArea;
	}

	/**
	 * Return the caption area.
	 *
	 * @return \Libchart\View\Primitive\Rectangle caption area
	 */
	public function getCaptionArea() {
		return $this->captionArea;
	}

	/**
	 * Return the text color.
	 *
	 * @return text color
	 */
	public function getTextColor() {
		return $this->textColor;
	}
	
	/**
	 * 
	 * @param int $printImageBorder self::IMAGE_PRINT_BORDER_*
	 */
	public function setPrintImageBorder($printImageBorder) {
		$this->printImageBorder = $printImageBorder;
		return $this;
	}

}
<?php
require_once('Exceptions.php');
require_once('lib/pChart/pData.class');
require_once('lib/pChart/pChart.class');
require_once('lib/pChart/pCache.class');

/**
 * Class that implements creating the graphs
 * example of usage
 * <code>
 * $x = array(1,2,3,4,5,6);
 * $y = array('serie1' => array(1,2,3,4,5,6));
 * $diag = new Diagram(400, 400);
 * $diag->fillData($x, $y)->setSkin('White')->drawPieGraph();
 * </code>
 */
class Diagram{
	/**
	 * Skins for the graphs
	 */
	static private $skins = array(
		'Winter' => array(
			'GradientBackground' => array('R' => 132,'G' => 153,'B' => 172,'Decay' => 50),
			'GradientGraphArea' => array('R' => 162,'G' => 183,'B' => 202,'Decay' => 50),
			'GraphArea' => array('R' => 255,'G' => 255,'B' => 255),
			'Scale' => array('R' => 255,'G' => 255,'B' => 255),
			'Grid' => array('R' => 230,'G' => 230,'B' => 230, 'alpha' => 20),
			'Treshold' => array('R' => 143,'G' => 55,'B' => 72),
			'PlotGraph' => array('R' => 255,'G' => 255,'B' => 255),
			'Title' => array('R' => 255,'G' => 255,'B' => 255,'bgR' => 0,'bgG' => 0,'bgB' => 0, 'alpha' => 50),
			'Legend' => array('bgR' => 236,'bgG' => 238,'bgB' => 240,'sR' => 52,'sG' => 58,'sB' => 82)
		),
		'White' => array(
			'GradientBackground' => array('R' => 255,'G' => 255,'B' => 255,'Decay' => 1),
			'GradientGraphArea' => array('R' => 255,'G' => 255,'B' => 255,'Decay' => 1),
			'GraphArea' => array('R' => 255,'G' => 255,'B' => 255),
			'Scale' => array('R' => 0,'G' => 0,'B' => 0),
			'Grid' => array('R' => 230,'G' => 230,'B' => 230, 'alpha' => 20),
			'Treshold' => array('R' => 143,'G' => 55,'B' => 72),
			'PlotGraph' => array('R' => 255,'G' => 255,'B' => 255),
			'Title' => array('R' => 255,'G' => 255,'B' => 255,'bgR' => 0,'bgG' => 0,'bgB' => 0, 'alpha' => 50),
			'Legend' => array('bgR' => 236,'bgG' => 238,'bgB' => 240,'sR' => 52,'sG' => 58,'sB' => 82)
		)
	);

	/**
	 * Image width
	 * @var integer
	 */
	private $width;

	/**
	 * Image height
	 * @var integer
	 */
	private $height;

	/**
	 * Prepared data for creating the graphs
	 * @var pData
	 */
	private $DataSet = null;

	/**
	 * Graph object
	 * @var pChart
	 */
	private $graph;

	/**
	 * Namber of the x labels
	 * @var integer
	 */
	private $xNumber = 0;

	/**
	 * Number of the series
	 * @var integer
	 */
	private $seriesNumber = 0;

	private $isShowLegends = true;
	private $isShowTicks = true;
	private $valueTreshold = 0;
	private $fontSizeLegend = 8;
	private $fontSizeTreshold = 6;
	private $skin;
	private $font;
	private $hash;

	/**
	 * Cache object
	 * @var pCache
	 */
	private $Cache = null;

	/**
	 * Constructor of the class
	 * @param integer $width width of the image
	 * @param integer $height height of the image
	 * @param string $skin skin of the graph
	 * @param string $dirCache -- directory for caching. If null cache will be disabled
	 * @throws ParamException
	 */
	public function  __construct($width, $height, $skin = '', $dirCache = null) {
		$this->width = intval($width);
		$this->height = intval($height);
		if(!empty($skin)){
			$this->setSkin($skin);
		}
		$this->font = dirname(__FILE__)."/fonts/tahoma.ttf";
		if($dirCache === null){
			return;
		}
		if(!is_string($dirCache)){
			throw new ParamException("dirCache should be a string");
		}
		if(!is_writable($dirCache)){
			throw new ParamException("Please make sure that the directory $dirCache is writable by Web server process.");
		}
		$this->Cache = new pCache($dirCache);
	}

	/**
	 * Prepears the data to create the graph image
	 * @param array $xdata array of the values for x axis for line or bar graphs
	 * or array of labels for the pie graph
	 * @param array $ydata two dimmentional array of values.
	 * the keys of the first level can be legends for the line or bar graphs
	 * For pie graph the array must have only 1 subarray of values
	 * @return Diagram
	 * @throws ParamException
	 */
	public function fillData($xdata, $ydata){
		if(!is_array($xdata) || !count($xdata)){
			throw new ParamException("xdata should be an array and have at least 1 value");
		}

		if(!is_array($ydata) || !count($ydata)){
			throw new ParamException("ydata should be an array and have at least 1 serie of the values");
		}
		$this->DataSet = new pData;
		$this->DataSet->AddPoint($xdata,"xdata");

		$this->xNumber = count($xdata);

		foreach($ydata as $name => $values){
			if(!is_array($values) || (count($values) != $this->xNumber)){
				throw new ParamException("All series in ydata should have the same number of values like xdata");
			}
			$this->DataSet->AddPoint($values,$name);
		}
		$this->seriesNumber = count($ydata);

		$this->DataSet->AddAllSeries();
		$this->DataSet->RemoveSerie("xdata");
		$this->DataSet->SetAbsciseLabelSerie("xdata");
		return $this;
	}

	/**
	 * Sets the names for the axises
	 * @param string $xlegend legend for x axis
	 * @param string $ylegend legend for y axis
	 * @return Diagram
	 * @throws ParamException
	 * @throws Exception
	 */
	public function setAxisLegendNames($xlegend, $ylegend){
		$this->isFullData();

		if(!is_string($xlegend)){
			throw new ParamException("xlegend should be a string");
		}

		if(!is_string($ylegend)){
			throw new ParamException("ylegend should be a string");
		}

		$this->DataSet->SetXAxisName($xlegend);
		$this->DataSet->SetYAxisName($ylegend);
		return $this;
	}

	/**
	 *
	 * @param array $legends named array of the legends for the added series
	 * The keys should have the same value like the keys of the ydata for the fillData method
	 * @return Diagram
	 * @throws ParamException
	 * @throws Exception
	 */
	public function setLegendNames($legends){
		$this->isFullData();

		if(!is_array($legends) || (count($legends) != $this->seriesNumber)){
			throw new ParamException("legends should be an array and have the same count of values like the ydata for the fillData method");
		}

		foreach($legends as $name => $legend){
			$this->DataSet->SetSerieName($legend,$name);
		}
		return $this;
	}

	/**
	 * Sets the skin for the graph
	 * @param string $skin
	 * @return Diagram
	 * @throws ParamException
	 */
	public function setSkin($skin){
		if(!is_string($skin) || !isset(self::$skins[$skin])){
			throw new ParamException('skin should be a string and it should exist in the current array of the skins');
		}
		$this->skin = self::$skins[$skin];
		return $this;
	}

	/**
	 * Sets size of font for the legends
	 * @param integer $size
	 * @return Diagram
	 * @throws ParamException
	 */
	public function setFontSizeLegend($size){
		if(intval($size) < 1){
			throw new ParamException('Font size should be more 0');
		}
		$this->fontSizeLegend = intval($size);
		return $this;
	}

	/**
	 * Sets size of font for the treshold
	 * @param integer $size
	 * @return Diagram
	 * @throws ParamException
	 */
	public function setFontSizeTreshold($size){
		if(intval($size) < 1){
			throw new ParamException('Font size should be more 0');
		}
		$this->fontSizeTreshold = intval($size);
		return $this;
	}

	/**
	 * Should the legend be shown or not
	 * @param boolean $value
	 * @return Diagram
	 */
	public function setShowLegends($value){
		$this->isShowLegends = !empty($value);
		return $this;
	}

	/**
	 * Should be shown ticks and legends for the axis or not
	 * @param boolean $value
	 * @return Diagram
	 * @throws ParamException
	 */
	public function setShowTicks($value){
		$this->isShowTicks = !empty($value);
		return $this;
	}

	/**
	 * Sets the value for the drawing treshold
	 * @param false|float $value if false the treshold won't be drawn
	 * @return Diagram
	 * @throws ParamException
	 */
	public function setValueTreshold($value){
		if(!is_scalar($value)){
			throw new ParamException('The value should be false or float');
		}

		if($value === false){
			$this->valueTreshold = false;
		}else{
			$this->valueTreshold = floatval($value);
		}
		return $this;
	}

	/**
	 * Creates the linear graph and out it
	 * @param string $type typeof the graph. Can be Line, Cubic, Area
	 * @param integer $SkipLabels draw 1 x label for every $SkipLabels labels
	 * @param boolean $DrawPoints if true the the values will be shown on the graph by points
	 * @param string $filename if not empty then the image will be saved to the $filename file
	 * else it will be sent directly to the browser
	 */
	public function drawLineGraph($type, $SkipLabels = 1, $DrawPoints = true, $filename = false){
		$this->isFullData();

		if(!is_string($type)){
			throw new ParamException('type should be a string');
		}
		$this->checkSkipLabels($SkipLabels);
		$this->checkFilename($filename);

		$this->checkInCache($filename, $type, $SkipLabels, $DrawPoints);

		$this->prepareGraphArea($SkipLabels);
		switch($type){
			case 'Line':
				// Draw the line graph
				$this->graph->drawLineGraph($this->DataSet->GetData(),$this->DataSet->GetDataDescription());
				if($DrawPoints){
					$this->drawPoints();
				}
				break;
			case 'Cubic':
				$this->graph->drawCubicCurve($this->DataSet->GetData(),$this->DataSet->GetDataDescription(),3);
				if($DrawPoints){
					$this->drawPoints();
				}
				break;
			case 'Area':
				$this->graph->drawFilledLineGraph($this->DataSet->GetData(),$this->DataSet->GetDataDescription(),50);
				if($DrawPoints){
					$this->drawPoints();
				}
				break;
			default:
				throw new ParamException("$type does not implemented");
		}

		if($this->isShowLegends){
			$this->drawLegend();
		}
		$this->Render($filename);
	}

	/**
	 * Creates the bar graph and out it
	 * @param integer $SkipLabels draw 1 x label for every $SkipLabels labels
	 * @param string $filename if not empty then the image will be saved to the $filename file
	 * else it will be sent directly to the browser
	 */
	public function drawBarGraph($SkipLabels = 1, $filename = false){
		$this->isFullData();
		$this->checkSkipLabels($SkipLabels);
		$this->checkFilename($filename);

		$this->checkInCache($filename, "Bar", $SkipLabels);

		$this->prepareGraphArea($SkipLabels);
		$this->graph->drawBarGraph($this->DataSet->GetData(),$this->DataSet->GetDataDescription());
		if($this->isShowLegends){
			$this->drawLegend();
		}
		$this->Render($filename);
	}

	/**
	 * Creates the pie graph and out it
	 * @param string $filename if not empty then the image will be saved to the $filename file
	 * else it will be sent directly to the browser
	 */
	public function drawPieGraph($filename = false){
		$this->isFullData();
		if($this->seriesNumber > 1){
			throw new ParamException("you entered more then 1 serie to the data. Pie chart can only accept one serie of data");
		}
		$this->checkFilename($filename);

		$this->checkInCache($filename, "Pie");

		// Initialise the graph
		$this->preparePieGraphArea();
		$this->graph->drawBasicPieGraph(
			$this->DataSet->GetData(),
			$this->DataSet->GetDataDescription(),
			$this->width/2,$this->height/2, min(array($this->width/2,$this->height/2)) - 5
		);
		if($this->isShowLegends){
			$this->graph->setFontProperties($this->font,$this->fontSizeLegend);
			$this->graph->drawPieLegend(
				$this->width - 70,15,
				$this->DataSet->GetData(),
				$this->DataSet->GetDataDescription(),
				$this->skin['Legend']['bgR'],
				$this->skin['Legend']['bgG'],
				$this->skin['Legend']['bgB']
			);
		}
		$this->Render($filename);
	}

	private function prepareGraphArea($SkipLabels){
		$this->graph = new pChart($this->width, $this->height);
		$this->graph->setFontProperties($this->font,$this->fontSizeLegend);
		$this->graph->drawFilledRoundedRectangle(
			0, 0, $this->width, $this->height, 10,
			$this->skin['GradientBackground']['R'],
			$this->skin['GradientBackground']['G'],
			$this->skin['GradientBackground']['B']
		);

		if($this->isShowTicks){
			$this->graph->setGraphArea(60,20,$this->width - 20, $this->height - 60);
		}else{
			$this->graph->setGraphArea(10,10,$this->width - 10, $this->height - 10);
		}
		$this->graph->drawGraphArea(
			$this->skin['GraphArea']['R'],
			$this->skin['GraphArea']['G'],
			$this->skin['GraphArea']['B'],
			TRUE
		);
		$this->graph->drawScale(
			$this->DataSet->GetData(),
			$this->DataSet->GetDataDescription(),
			SCALE_NORMAL,
			$this->skin['Scale']['R'],
			$this->skin['Scale']['G'],
			$this->skin['Scale']['B'],
			$this->isShowTicks,45,2,TRUE,$SkipLabels
		);
		if($this->skin['GradientGraphArea']['Decay']){
			$this->graph->drawGraphAreaGradient(
				$this->skin['GradientGraphArea']['R'],
				$this->skin['GradientGraphArea']['G'],
				$this->skin['GradientGraphArea']['B'],
				$this->skin['GradientGraphArea']['Decay']
			);
		}
		$this->graph->drawGrid(
			1,TRUE,
			$this->skin['Grid']['R'],
			$this->skin['Grid']['G'],
			$this->skin['Grid']['B'],
			$this->skin['Grid']['alpha'],
			$SkipLabels
		);

		if($this->valueTreshold !== false){
			$this->graph->setFontProperties($this->font,$this->fontSizeTreshold);
			$this->graph->drawTreshold(
				$this->valueTreshold,
				$this->skin['Treshold']['R'],
				$this->skin['Treshold']['G'],
				$this->skin['Treshold']['B'],
				TRUE,TRUE
			);
		}
	}

	/**
	 * Draws the legends
	 */
	private function drawLegend(){
		$this->graph->setFontProperties($this->font,$this->fontSizeLegend);
		$this->graph->drawLegend(
			$this->width - 200,0,
			$this->DataSet->GetDataDescription(),
			$this->skin['Legend']['bgR'],
			$this->skin['Legend']['bgG'],
			$this->skin['Legend']['bgB'],
			$this->skin['Legend']['sR'],
			$this->skin['Legend']['sG'],
			$this->skin['Legend']['sB']
		);
	}

	/**
	 * Preparing graph for "Pie" type graph
	 */
	private function preparePieGraphArea(){
		$this->graph = new pChart($this->width, $this->height);
		$this->graph->drawFilledRoundedRectangle(
			0, 0, $this->width, $this->height, 10,
			$this->skin['GradientBackground']['R'],
			$this->skin['GradientBackground']['G'],
			$this->skin['GradientBackground']['B']
		);
	}

	/**
	 * Draws the points for "Line" type of the graph
	 */
	private function drawPoints(){
		$this->graph->drawPlotGraph(
			$this->DataSet->GetData(),
			$this->DataSet->GetDataDescription(),3,2,
			$this->skin['PlotGraph']['R'],
			$this->skin['PlotGraph']['G'],
			$this->skin['PlotGraph']['B']
		);
	}

	private function isFullData() {
		if(!$this->DataSet){
			throw new Exception("The data is empty. Fill the data first");
		}
		return true;
	}

	private function checkFilename($filename){
		if(!empty($filename) && !is_string($filename)){
			throw new ParamException('filename should be false or a string');
		}
		return true;
	}

	/**
	 * Checks correctness of the $SkipLabels parameter
	 * @param integer $SkipLabels
	 */
	private function checkSkipLabels(&$SkipLabels){
		if(!is_numeric($SkipLabels)){
			throw new ParamException('SkipLabels should be a number');
		}
		$SkipLabels = intval($SkipLabels);
		if($SkipLabels < 1){
			throw new ParamException('SkipLabels should be not lesser than 1');
		}
	}

	// Render the picture
	private function Render($filename){
		if(!empty($filename)){
			$this->graph->Render($filename);
		}else{
			$this->Cache->WriteToCache($this->hash,$this->DataSet->GetData(),$this->graph);
			$this->graph->Stroke();
		}
	}

	/**
	 * Checks if the graph already exists in the cache. If yes it will be outputed
	 * to the browser
	 * @param string $filename
	 * @param string $type
	 * @param integer $SkipLabels
	 * @param boolean $DrawPoints
	 */
	private function checkInCache($filename, $type, $SkipLabels = 1, $DrawPoints = true){
		if(!$filename && ($this->Cache !== null)){ //if we will use cache and we will return image to browser
			$this->hash = $this->calcSettingsHash($type, $SkipLabels, $DrawPoints);
			$this->Cache->GetFromCache($this->hash,$this->DataSet->GetData());
		}
	}

	/**
	 * Calculates the hash for the current graph settings.
	 * @param string $type type of graph
	 * @param integer $SkipLabels
	 * @param boolean $DrawPoints
	 * @return string
	 */
	private function calcSettingsHash($type, $SkipLabels = 1, $DrawPoints = true){
		$settings = array(
			$this->width, $this->height, $this->skin, $this->fontSizeLegend,
			$this->fontSizeTreshold, $this->isShowLegends, $this->isShowTicks,
			$this->valueTreshold, $type, $SkipLabels = 1, $DrawPoints
		);
		return md5(serialize($settings));
	}
}

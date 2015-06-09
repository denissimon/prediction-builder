<?php
/**
 * PredictionBuilder - A library for machine learning that builds predictions using a linear regression.
 *
 * @author   Denis Simon <hellodenissimon@gmail.com>
 *
 * @license  Licensed under MIT (https://github.com/denissimon/prediction-builder/blob/master/LICENSE)
 */

namespace PredictionBuilder;

trait readData {
    
    /**
     * Reading the data from an array
     * 
     * @param array $data
     *
     * @throws \RuntimeException
     */
    public function readFromArray(array $data) {
        $callback =
        function ($v, $k) use ($data) {
            if ((!isset($v[0])) || (!isset($v[1]))) {
                throw new \RuntimeException('Mismatch in the number of x and y in a data set.');
            } else {
            	$this->xVector[] = $v[0];
            	$this->yVector[] = $v[1];
            }
        };    
        array_walk($data, $callback);
    }
}

class PredictionBuilder {
    protected $x = 0;
    protected $data = [];
    protected $xVector = [];
    protected $yVector = [];
    protected $count = 0;
    
    use readData;
    
    /**
     * Constructor
     *
     * @param integer|float $x
     * @param array $data
     */
    public function __construct($x, array $data) {
        if (is_numeric($x))
            $this->x = (float)$x;
        if (is_array($data))
            $this->data = $data;

        $this->readFromArray($this->data);
        $this->count = count($this->data);
    }
	
    /**
     * Magic overloading method
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @throws Exception when the method doesn't exist
     */
    public function __call($name, $arguments)
    {
    	throw new \Exception("No such method exists: $name (".implode(', ', $arguments).")");
    }
	
    /** 
     * Sum of the x values
     * 
     * @param array $xVector
     * 
     * @return float|integer
     */
    private function sumX(array $xVector) {
        $sumX = 0;
        foreach ($xVector as $key=>$value) {
            $sumX += $value;
        }
        return $sumX;
    }

    /** 
     * Sum the y values
     *
     * @param array $yVector
     * 
     * @return float|integer
     */
    private function sumY(array $yVector) {
        $sumY = 0;
        foreach ($yVector as $key=>$value) {
            $sumY += $value;
        }
        return $sumY;
    }
    
    /* 
     * Sum of the product of x and y
     *
     * @param array $data
     *
     * @return float|integer
     */
    private function sumXY(array $data) {
        $sumXY = 0;
        foreach ($data as $key=>$value) {
            $sumXY += $value[0] * $value[1];
        }
        return $sumXY;
    }

    /** 
     * Sum of x squared values
     *
     * @param array $xVector
     * 
     * @return float|integer
     */
    private function sumXSquared(array $xVector) {
        $sumXSquared = 0;
        foreach ($xVector as $key=>$value) {
            $sumXSquared += $this->square($value);
        }
        return $sumXSquared;
    }
    
    /** 
     * Sum of y squared values
     *
     * @param array $yVector
     * 
     * @return float|integer
     */
    private function sumYSquared(array $yVector) {
        $sumYSquared = 0;
        foreach ($yVector as $key=>$value) {
            $sumYSquared += $this->square($value);
        }
        return $sumYSquared;
    }
    
    /**
     * @param float|integer $value
     * 
     * @return float|integer
     */
    private function square($value) {
        return $value * $value;
    }
    
    /**
     * Dispersion of x
     * Dx = (ΣX2 / N) - (ΣX / N)2
     *
     * @return float|integer
     */
    private function xDispersion () {
        return 
        (($this->sumXSquared($this->xVector)/$this->count) - 
        $this->square($this->sumX($this->xVector)/$this->count));
    }
    
    /**
     * Dispersion of y
     * Dy = (Σy2 / N) - (Σy / N)2
     *
     * @return float|integer
     */
    private function yDispersion () {
        return 
        (($this->sumYSquared($this->yVector)/$this->count) - 
        $this->square($this->sumY($this->yVector)/$this->count));
    }
    
    /**
     * The intercept
     * a = (ΣY - b(ΣX)) / N
     *
     * @return float
     */
    private function aIntercept ($b) {
        return
        (float)($this->sumY($this->yVector)/$this->count) - 
        ($b * ($this->sumX($this->xVector)/$this->count));
    }
    
    /**
     * The slope, or the regression coefficient
     * b = ((ΣXY / N) - (ΣX / N)(ΣY / N)) / ((ΣX2 / N) - (ΣX / N)2)
     *
     * @return float
     */
    private function bSlope () {
        return 
        (float)(($this->sumXY($this->data)/$this->count) - 
        (($this->sumX($this->xVector)/$this->count) * 
        ($this->sumY($this->yVector)/$this->count))) /
        $this->xDispersion();
    }

    /**
     * The Pearson's correlation coefficient
     * Rxy = b * (sqrt(Dx) / sqrt(Dy))
     *
     * @param float $b
     * 
     * @return float
     */
    private function corCoefficient ($b) {
        return $b * (sqrt($this->xDispersion()) / sqrt($this->yDispersion()));
    }
	
    /**
     * Creating a linear model that fits the data.
     * The resulting equation has the form: h(x) = a + bx
     *
     * @param float $a
     * @param float $b
     * 
     * @return Closure
     */
    private function createModel($a, $b)
    {
        return function($x) use ($a, $b) { 
            return $a + $b*$x;
        };
    }
	
    /**
     * @param float $number
     * 
     * @return float
     */
    private function round_($number) {
    	return round($number, 5);
    }
	
    /**
     * Building the prediction of the expected value of y with the given x, based on a linear regression model.
     *
     * @return object
     *
     * @throws \RuntimeException
     */
    public function build() {
        // Check the number of observations in the given data set
        if ($this->count < 3) {
            throw new \RuntimeException('The data set should contain a minimum of 3 observations.');
        }
        
        $b = $this->round_($this->bSlope());
        $a = $this->round_($this->aIntercept($b));
        $model = $this->createModel($a, $b);
        $y = $this->round_($model($this->x));

        $result = new \stdClass();
        $result->ln_model = (string)($a.'+'.$b.'x');
        $result->cor = $this->round_($this->corCoefficient($b));
        $result->x = $this->x;
        $result->y = $y;
        
        return $result;
    }
}

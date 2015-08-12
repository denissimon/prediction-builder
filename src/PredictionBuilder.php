<?php
/**
 * PredictionBuilder - A library for machine learning that builds predictions using a linear regression.
 *
 * @author   Denis Simon <denis.v.simon@gmail.com>
 *
 * @license  Licensed under MIT (https://github.com/denissimon/prediction-builder/blob/master/LICENSE)
 */

namespace PredictionBuilder;

trait readData {

    /**
     * Reads the data from a given array
     * 
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    public function readFromArray(array $data) {
        $callback = 
        function ($v) {
            if ((!isset($v[0])) || (!isset($v[1]))) {
                throw new \InvalidArgumentException('Mismatch in the number of x and y in the dataset.');
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
     * @param number $x
     * @param array  $data
     */
    public function __construct($x, array $data) {
        if (is_numeric($x)) 
            $this->x = (float) $x;
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
     * @throws \Exception when the method doesn't exist
     */
    public function __call($name, $arguments) {
        throw new \Exception("No such method exists: $name (".implode(', ', $arguments).")");
    }

    /**
     * @param number $v
     *
     * @return number
     */
    private function square($v) {
        return $v * $v;
    }

    /** 
     * Sum of the vector values
     * 
     * @param array $vector
     *
     * @return number
     */
    private function sum(array $vector) {
        return array_reduce(
            $vector, 
            function($v, $w) { return $v + $w; }
        );
    }
    
    /** 
     * Sum of the vector squared values
     *
     * @param array $vector
     *
     * @return number
     */
    private function sumSquared(array $vector) {
        return array_reduce(
            $vector, 
            function($v, $w) { return $v += $this->square($w); }
        );
    }

    /* 
     * Sum of the product of x and y
     *
     * @param array $data
     *
     * @return number
     */
    private function sumXY(array $data) {
        return array_reduce(
            $data,
            function($v, $w) { return $v += $w[0] * $w[1]; }
        );
    }
    
    /**
     * The dispersion
     * Dv = (Σv2 / N) - (Σv / N)2
     *
     * @param string $v 'x' or 'y'
     *
     * @return number
     */
    private function dispersion($v) {
        return (($this->sumSquared($this->{$v.'Vector'}) / $this->count) - 
        $this->square($this->sum($this->{$v.'Vector'}) / $this->count));
    }
    
    /**
     * The intercept
     * a = (ΣY - b(ΣX)) / N
     *
     * @param float $b
     *
     * @return float
     */
    private function aIntercept($b) {
        return (float) ($this->sum($this->yVector) / $this->count) - 
        ($b * ($this->sum($this->xVector) / $this->count));
    }
    
    /**
     * The slope, or the regression coefficient
     * b = ((ΣXY / N) - (ΣX / N)(ΣY / N)) / ((ΣX2 / N) - (ΣX / N)2)
     *
     * @return float
     */
    private function bSlope() {
        return (float) (($this->sumXY($this->data) / $this->count) - 
        (($this->sum($this->xVector) / $this->count) * 
        ($this->sum($this->yVector) / $this->count))) / 
        $this->dispersion('x');
    }

    /**
     * The Pearson's correlation coefficient
     * Rxy = b * (sqrt(Dx) / sqrt(Dy))
     *
     * @param float $b
     *
     * @return float
     */
    private function corCoefficient($b) {
        return $b * (sqrt($this->dispersion('x')) / sqrt($this->dispersion('y')));
    }

    /**
     * Creats a linear model that fits the data.
     * The resulting equation has the form: h(x) = a + bx
     *
     * @param float $a
     * @param float $b
     *
     * @return \Closure
     */
    private function createModel($a, $b) {
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
     * Builds the prediction of the expected value of y with the given x, based on a linear regression model.
     *
     * @return \stdClass
     *
     * @throws \InvalidArgumentException
     */
    public function build() {
        // Check the number of observations in a given dataset
        if ($this->count < 3) {
            throw new \InvalidArgumentException('The dataset should contain a minimum of 3 observations.');
        }
        
        $b = $this->round_($this->bSlope());
        $a = $this->round_($this->aIntercept($b));
        $model = $this->createModel($a, $b);
        $y = $this->round_($model($this->x));
        
        $result = new \stdClass();
        $result->ln_model = (string) ($a.'+'.$b.'x');
        $result->cor = $this->round_($this->corCoefficient($b));
        $result->x = $this->x;
        $result->y = $y;
        
        return $result;
    }
}

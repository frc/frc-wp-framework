<?php

namespace FRC;

abstract class Base_Ajax_Endpoint extends Base_Class {

    public $endpoint_name;

    public $params = [];

    public function __construct () {
        $this->def();
    }

    public function setup_params ($params) {
        $this->params = $params;
    }

    abstract public function get_data ();

    /**
     * Just some methods that are called at different times of the program.
     */
    public function init () {}

    public function def () {}

    public function saved () {}

    public function pre_saved () {}
}
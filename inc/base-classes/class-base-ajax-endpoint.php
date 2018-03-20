<?php

namespace FRC;

abstract class Base_Ajax_Endpoint {

    public $endpoint_name;

    public $params = [];

    public function __construct () {
        $this->def();
    }

    public function setup_params ($params) {
        $this->params = $params;
    }

    abstract public function get_data ();

    public function def () {}
}
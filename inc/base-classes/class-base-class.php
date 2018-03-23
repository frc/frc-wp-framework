<?php

namespace FRC;

abstract class Base_Class {
    /**
     * Needed options members
     */
    public $acf_schema          = [];
    public $acf_schema_groups   = [];
    public $options             = [];
    public $args                = [];

    /**
     * Fields that contain data
     */
    public      $acf_fields;

    public function __construct() {
        $this->def();
    }

    /**
     * Just some methods that are called at different times of the program.
     */
    public function def () {}
    public function init () {}
    public function saved () {}
    public function pre_saved() {}
}
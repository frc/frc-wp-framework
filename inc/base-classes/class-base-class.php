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
    public $schema              = null; // Schema handler object

    /**
     * Fields that contain data
     */
    public      $acf_fields;

    public function __construct() {
        $this->schema();
        $this->def();
    }

    /**
     * Just some methods that are called at different times of the program.
     */
    public function def () {}
    public function init () {}
    public function saved () {}
    public function pre_saved() {}

    public function schema () {
        if($this->schema != null) {
            return $this->schema;
        }

        return ($this->schema = new Schema($this));
    }

    public function get_key_name () {
        return $this->options['key_name'] ?? api_name_to_key(get_class($this));
    }

    public function get_key_group_key_name () {
        return str_replace("_", "", $this->get_key_name() . '_fields');
    }
}
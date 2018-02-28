<?php

namespace FRC;

abstract class Taxonomy_Base_Class {

    /**
     * These are fields that can be overwritten
     */
    public $args                = [];
    public $acf_schema          = [];
    public $acf_schema_groups   = [];

    /**
     * These are data fields
     */
    public $acf_fields          = [];

    public function __construct () {
        $this->def();
    }

    public function def () {
    }


}
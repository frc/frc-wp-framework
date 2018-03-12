<?php
namespace FRC;

abstract class Component_Base_Class {
    /**
     * Options that can be overridden in the child post class
     */
    public $acf_schema              = [];
    public $acf_schema_groups       = [];
    public $options                 = [];
    public $tags                    = [];

    /**
     * Fields that contain data
     */
    public $acf_fields          = [];
    public $parent_post_id      = null;

    /**
     * Internal data for the component functionality
     */
    protected $component_view_file     = "";
    protected $component_path          = "";

    public function __construct () {
        $this->def();

        $this->set_component_path();
    }

    public function prepare ($data) {
        $this->acf_fields = (object) $data;
        
        $this->init();
    }

    public function prepare_data ($data) {
        unset($data->acf_fc_layout);
        
        $data->component = $this;

        return (object) $data;
    }

    public function render () {
        global $frc_current_component_render_path;

        if(empty($this->component_view_file)) {
            trigger_error("Trying to render a component, but the component view file (" . $this->component_view_file . ") is empty.", E_USER_ERROR);
            return;
        }

        $render_data = $this->prepare_data($this->acf_fields);

        $frc_current_component_render_path = $this->component_path;

        return api_render($this->component_view_file, $render_data);
    }

    public function set_component_path () {
        $this->component_path = api_get_component_path(get_class($this));

        $this->component_view_file = $this->component_path . '/view.php';
    }

    public function get_component_types () {
        return (is_string($this->component_types)) ? [$this->component_types] : $this->component_types;
    }

    public function get_key_name () {
        return api_name_to_key(get_class($this));
    }

    public function get_label () {
        return api_name_to_proper(get_class($this));
    }

    public function get_tags () {
        return array_map('strtolower', $this->tags);
    }

    public function pre_save () {
        $this->pre_saved();
    }

    public function save () {
        $this->saved();
    }

    /**
     * Just some methods that are called at different times of the program.
     */
    protected function init () {}

    protected function def () {}

    protected function saved () {}

    protected function pre_saved() {}
}
<?php
namespace FRC;

abstract class Component_Base_Class {
    public $acf_schema              = [];
    public $acf_schema_groups       = [];

    public $component_data          = [];
    
    public $component_view_file     = "";
    public $component_path          = "";

    public function __construct () {
        $this->definition();

        $this->set_component_path();
    }

    public function prepare ($data) {
        $this->component_data = $data;
        
        $this->init();
    }

    public function definition () {
    }

    public function init () {
    }

    public function prepare_data ($data) {
        unset($data['acf_fc_layout']);
        
        $data['component'] = $this;

        return (object) $data;
    }

    public function render () {
        if(empty($this->component_view_file)) {
            trigger_error("Trying to render a component, but the component view file (" . $this->component_view_file . ") is empty.", E_USER_ERROR);
            return;
        }

        $component_data = $this->component_data;

        $render_data = $this->prepare_data($component_data);

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
}
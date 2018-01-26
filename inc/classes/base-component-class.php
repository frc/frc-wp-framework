<?php

class FRC_Base_Component_Class implements FRC_Base_Component_Interface {
    public $acf_schema              = [];
    public $child_components        = [];
    public $component_type          = "basic";

    public $component_data          = [];
    
    public $component_view_file     = "";

    final public function prepare ($data) {
        $this->component_data = $data;

        $this->init();
    }

    public function init () {
    }

    public function prepare_data () {
        $data = $this->component_data;
        unset($data['acf_fc_layout']);
        return $data;
    }

    public function render () {
        if(empty($this->component_view_file))
            return;

        $render_data = $this->prepare_data();

        return frc_api_render($this->component_view_file, $render_data);
    }

    public function get_key_name () {
        return frc_api_name_to_key(get_class($this));
    }

    public function get_label () {
        return frc_api_class_name_to_proper(get_class($this));        
    }
}
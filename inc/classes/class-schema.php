<?php

namespace FRC;

class Schema {
    private $parent;

    public function __construct ($parent) {
        $this->parent = $parent;
    }

    public function add_text_field ($name, $label, $options = []) {
        return $this->add_field($name, $label, 'text', $options);
    }

    public function add_field($name, $label, $type, $options = []) {
        $acf_field_args = [
            'name' => $name,
            'label' => $label,
            'type' => $type
        ];

        $acf_field_args = api_proof_acf_schema_item($acf_field_args, $this->parent->get_key_group_key_name());

        $acf_field_args = array_replace_recursive($acf_field_args, $options);

        $this->parent->acf_schema[] = $acf_field_args;

        return $this;
    }
}
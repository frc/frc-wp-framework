<?php

namespace FRC;

const ACF_FIELD_TEXT            = "text";
const ACF_FIELD_TEXTAREA        = "textarea";
const ACF_FIELD_BOOLEAN         = "true_false";
const ACF_FIELD_ACCORDION       = "accordion";
const ACF_FIELD_BUTTON_GROUP    = "button-group";
const ACF_FIELD_CHECKBOX        = "checkbox";

class Schema {
    private $parent;
    private $acf_field_types = [
        ACF_FIELD_TEXT,
        ACF_FIELD_TEXTAREA,
        ACF_FIELD_BOOLEAN,
        ACF_FIELD_ACCORDION,
        ACF_FIELD_BUTTON_GROUP,
        ACF_FIELD_CHECKBOX
    ];

    public function __construct ($parent) {
        $this->parent = $parent;
    }

    public function add_text_field ($name, $label, $options = []) {
        return $this->add_field($name, $label, ACF_FIELD_TEXT, $options);
    }

    public function add_textarea_field ($name, $label, $options = []) {
        return $this->add_field($name, $label, ACF_FIELD_TEXTAREA, $options);
    }

    public function add_field($name, $label, $type = ACF_FIELD_TEXT, $options = []) {
        if(!in_array($type, $this->acf_field_types)) {
            trigger_error("Unknown field type (" . $type . ") in " . get_class($this->parent) . ".", E_USER_ERROR);
        }

        $acf_field_args = [
            'name' => $name,
            'label' => $label,
            'type' => $type
        ];

        $acf_field_args = array_replace_recursive($acf_field_args, $options);

        $this->parent->acf_schema[] = $acf_field_args;

        return $this;
    }
}
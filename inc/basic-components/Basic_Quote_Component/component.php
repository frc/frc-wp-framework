<?php

class Basic_Quote_Component extends FRC_Base_Component_Class {
    public $acf_schema = [
        [
            'label' => 'Quote',
            'name'  => 'quote',
            'type'  => 'wysiwyg'
        ]
    ];

    public function init () {
    }
}
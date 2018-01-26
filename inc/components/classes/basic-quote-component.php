<?php

class Basic_Quote_Component extends FRC_Base_Component_Class {
    public $component_view_file = __DIR__ . "/../views/basic-quote-component.php";

    public $acf_schema = [
        [
            'label' => 'Quote',
            'name'  => 'quote',
            'type'  => 'wysiwyg'
        ]
    ];
}
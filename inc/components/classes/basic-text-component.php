<?php

class Basic_Text_Component extends FRC_Base_Component_Class {
    public $component_view_file = __DIR__ . "/../views/basic-text-component.php";

    public $acf_schema = [
        [
            'label' => 'Text',
            'name'  => 'text',
            'type'  => 'wysiwyg'
        ],
        [
            'label' => 'Image',
            'name'  => 'image',
            'type'  => 'image'
        ]
    ];
}
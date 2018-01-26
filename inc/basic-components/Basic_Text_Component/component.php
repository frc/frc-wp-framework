<?php

class Basic_Text_Component extends FRC_Base_Component_Class {
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
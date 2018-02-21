<?php

class Basic_Text_Component extends Component_Base_Class {
    public $component_types = [
        'base-component',
        'post-component',
        'page-component'
    ];

    public $acf_schema = [
        [
            'label' => 'Text',
            'name'  => 'text',
            'type'  => 'wysiwyg'
        ]
    ];
}
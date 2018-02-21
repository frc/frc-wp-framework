<?php

class Basic_Quote_Component extends Component_Base_Class {
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
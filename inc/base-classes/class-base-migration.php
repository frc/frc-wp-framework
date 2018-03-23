<?php

namespace FRC;

abstract class Migration_Base_Class extends Base_Class {

    public $version = false;

    abstract public function up ();

    abstract public function down ();
}
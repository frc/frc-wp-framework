<?php

namespace FRC;

abstract class Migration_Base_Class {

    public $version = false;

    public function __construct() {}

    abstract public function up ();

    abstract public function down ();
}
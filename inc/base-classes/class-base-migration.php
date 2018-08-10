<?php

namespace FRC;

abstract class Migration_Base_Class extends Base_Class {
    abstract public function up ();

    abstract public function down ();
}
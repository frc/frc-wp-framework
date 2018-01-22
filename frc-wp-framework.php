<?php
/* 
Plugin name: FRC WP Framework
Author: Taneli Heikkinen / Frantic Oy
Description: Helper functions for speeding up site and other stuff.
Licence: GPLv3 or later
Copyright: Taneli Heikkinen
Version: 0.1
*/

require_once 'inc/helpers.php';
require_once 'inc/functions.php';
require_once 'inc/transient-management.php';
require_once 'inc/custom-post-types.php';
require_once 'inc/base-post-class.php';
require_once 'inc/query.php';

//Set the default options
frc_set_options([
    'default_frc_post_class'    => 'FRC_Post',
    'cache_whole_post_objects'  => true
]);

define("FRC_WP_FRAMEWORK_INIT", true);
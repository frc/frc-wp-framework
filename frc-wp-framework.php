<?php
/* 
Plugin name: FRC WP Framework
Author: Taneli Heikkinen / Frantic Oy
Description: Frantic WP Framework. Experimental features and other helpers to aid development.
Licence: GPLv3 or later
Copyright: Taneli Heikkinen
Version: 0.1
*/

require_once 'inc/helpers.php';
require_once 'inc/functions.php';
require_once 'inc/transient-management.php';

require_once 'inc/classes/base-post-class.php';
require_once 'inc/classes/base-component-interface.php';
require_once 'inc/classes/base-component-class.php';

require_once 'inc/custom-post-types.php';
require_once 'inc/query.php';

require_once 'inc/basic-components/components.php';


//Set the default options
frc_set_options([
    'default_frc_post_class'    => 'FRC_Post',
    'cache_whole_post_objects'  => true
]);

define("FRC_WP_FRAMEWORK_INIT", true);
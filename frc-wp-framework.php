<?php
namespace FRC;

/* 
Plugin name: FRC WP Framework
Author: Taneli Heikkinen / Frantic Oy
Description: Frantic WP Framework. Experimental features and other helpers to aid development.
Licence: GPLv3 or later
Copyright: Taneli Heikkinen
Version: 0.1
*/


if(defined("FRC_WP_FRAMEWORK_INIT"))
    return;

require_once 'inc/classes/class-framework.php';
require_once 'inc/classes/class-attachment.php';
require_once 'inc/classes/class-term.php';
require_once 'inc/classes/class-query.php';

require_once 'inc/base-classes/class-base-post.php';
require_once 'inc/base-classes/class-base-component.php';
require_once 'inc/base-classes/class-base-taxonomy.php';

require_once 'inc/internal-helpers.php';
require_once 'inc/functions.php';
require_once 'inc/transient-management.php';

//Set the default options
set_options([
    'override_post_type_classes'        => [
        'post' => 'FRC\Post',
        'page' => 'FRC\Post'
    ],
    'local_cache_stack_size'            => 20,
    'cache_whole_post_objects'          => true,
    'setup_basic_post_type_components'  => true,
    'use_caching'                       => true
]);

define("FRC_WP_FRAMEWORK_INIT", true);
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

define("FRC_WP_FRAMEWORK_INIT", true);
define('FRC_COMPONENTS_KEY', 'frc_components');
define('FRC_PLUGIN_FILE_PATH', __FILE__);

require_once 'inc/install.php';

require_once 'inc/helpers/template.php';

require_once 'inc/admin/admin-page.php';
require_once 'inc/admin/admin-migrations.php';

require_once 'inc/class-framework.php';
require_once 'inc/classes/class-attachment.php';
require_once 'inc/classes/class-term.php';
require_once 'inc/classes/class-query.php';
require_once 'inc/classes/class-schema.php';
require_once 'inc/classes/class-data-container.php';
require_once 'inc/classes/class-render-data.php';
require_once 'inc/classes/class-component-data.php';

require_once 'inc/base-classes/class-base-class.php';

require_once 'inc/base-classes/class-base-post.php';
require_once 'inc/base-classes/class-base-option.php';
require_once 'inc/base-classes/class-base-component.php';
require_once 'inc/base-classes/class-base-taxonomy.php';
require_once 'inc/base-classes/class-base-migration.php';

require_once 'inc/default-classes/class-default-post.php';

require_once 'inc/internal-helpers.php';
require_once 'inc/functions.php';
require_once 'inc/transient-management.php';

require_once 'inc/cli/cli.php';

FRC::boot();
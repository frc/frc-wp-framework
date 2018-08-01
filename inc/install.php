<?php

namespace FRC;

function installation_steps () {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta("CREATE TABLE `{$wpdb->prefix}frc_transient_data` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `group_name` varchar(255) DEFAULT NULL,
                  `transient_key` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `group_name` (`group_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

register_activation_hook(FRC_PLUGIN_FILE_PATH, "FRC\installation_steps");

<?php

namespace FRC;

add_action('admin_menu', 'FRC\framework_admin_menu');

function framework_admin_menu () {
    add_menu_page( 'FRC Framework', 'FRC Framework', 'manage_options', 'frc-framework-options', 'FRC\framework_admin_options');
    add_submenu_page( 'frc-framework-options', 'Migrations', 'Migrations', 'manage_options', 'frc-framework-options-migrations', 'FRC\framework_admin_migrations' );
}

function framework_admin_options () {

}


function framework_admin_migrations () {
    $migrations = [];

    $current_migration_version = (int) get_option('frc_framework_migration_version');

    foreach(FRC::get_instance()->migration_classes as $class_name) {
        $version = (new $class_name)->get_version();

        $migrations[$version][] = [
            'name' => $class_name,
            'version' => $version,
            'done' => $current_migration_version >= $version
        ];
    }

    ksort($migrations);

    $layed_out_migrations = [];

    foreach($migrations as $version => $submigration) {
        foreach($submigration as $migration) {
            $migration['done'] = $migration['done'] ? 'Migrated' : 'Need to migrate
            ';
            $layed_out_migrations[] = $migration;
        }
    }

    echo '<h1>Migrations</h1>';

    //echo '<form method="post" action="' . admin_url('admin.php') . '"><input type="submit" value="Migrate" /><input type="hidden" name="page" value="frc-framework-options-migrations" /></form>';

    echo create_admin_page_table([
        'Name', 'Version', 'Status'
    ], $layed_out_migrations, 'migrations', [], []);
}
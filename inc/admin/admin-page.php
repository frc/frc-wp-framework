<?php

namespace FRC;

add_action('admin_menu', 'FRC\framework_admin_menu');

function framework_admin_menu () {
    add_menu_page( 'FRC Framework', 'FRC Framework', 'manage_options', 'frc-framework-options' );
    add_submenu_page( 'frc-framework-options', 'Migrations', 'Migrations', 'manage_options', 'frc-framework-options-migrations', 'FRC\framework_admin_migrations' );
}

function framework_admin_options () {

}


function framework_admin_migrations () {
    $migrations = [];

    $current_migration_version = get_current_migration_version();

    $migration_classes = FRC()->migration_classes;

    if(!empty($migration_classes)) {
        foreach ($migration_classes as $version => $classes) {
            foreach ($classes as $class_name) {
                $migrations[$version][] = [
                    'name'    => api_name_to_proper($class_name),
                    'version' => $version,
                    'done'    => $current_migration_version >= $version
                ];
            }
        }
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

    echo '<form method="post" action="' . admin_url('admin.php') . '"><input type="submit" value="Migrate all" name="migrate" /><input type="hidden" name="action" value="framework_migrate_run" /></form>';

    echo create_admin_page_table([
        'Name', 'Version', 'Status'
    ], $layed_out_migrations, 'migrations', [], []);
}

function framework_admin_migration_run () {
    set_time_limit(0);

    $migration_classes = FRC()->migration_classes;

    ksort($migration_classes);

    $current_migration_version = get_current_migration_version();

    foreach($migration_classes as $version => $classes) {
        if($current_migration_version >= $version) {
            continue;
        }

        foreach($classes as $class) {
            $class_instance = new $class();
            $class_instance->up();
        }

        set_current_migration_version($version);
    }

    wp_redirect(admin_url('admin.php') . '?page=frc-framework-options-migrations');
    exit;
}

add_action('admin_action_framework_migrate_run', 'FRC\framework_admin_migration_run');
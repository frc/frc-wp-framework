<?php
namespace FRC\CLI;

if(!class_exists("\WP_CLI"))
    return;

class Framework {
    /**
     * Scaffolding for easy framework usage.
     *
     * ## OPTIONS
     *
     * <what>
     * : What we are creating here. Valid options are: post-type, component and taxonomy.
     *
     * <name>
     * : What is the name of the class we are creating.
     *
     * [--output=<where>]
     * : What is the location where the new file is created
     *
     * ## EXAMPLES
     *
     *  wp frc create post-type News_Articles
     *
     * @when after_wp_load
     */
    public function create ($args, $assoc_args) {
        $frc_framework = \FRC\FRC::get_instance();

        $where = $frc_framework->root_folders['post-types'][0] ?? false;

        $what = strtolower($args[0]);
        $name = $args[1];

        if(isset($assoc_args['output']) && !empty($assoc_args['output'])) {
            $where = realpath($assoc_args['output']);
        }

        if(!$where) {
            \WP_CLI::error("Unknown destination. Define the destination where to create the new file.");
        }

        if(!file_exists($where) || (file_exists($where) && !is_dir($where))) {
            \WP_CLI::error($where . " not a valid directory.");
        }

        if(!in_array($what, ['post-type', 'component', 'taxonomy'])) {
            \WP_CLI::error($what . " is not a valid type.");
        }

        $new_file_location = rtrim($where, "/") . '/' . $name . '.php';

        $templates_dir = __DIR__ . '/templates';

        $replaces = [
            'CLASS_NAME' => $name
        ];

        $template_file = file_get_contents($templates_dir . '/' . $what . '.php');

        foreach($replaces as $replace => $replace_with) {
            $template_file = str_replace("___REPLACE_" . $replace . "___", $replace_with, $template_file);
        }

        file_put_contents($new_file_location, $template_file);

        \WP_CLI::success("New " . $what . " created in " . $new_file_location);
    }

    
}

\WP_CLI::add_command("frc", new Framework());
<?php
namespace FRC\CLI;

if(!class_exists("\WP_CLI"))
    return;

class Framework {

    private function get_directory_for_what ($what) {
        $frc_framework = \FRC\FRC::get_instance();

        $what_to_where = [
            'post-type' => 'post-types',
            'component' => 'components',
            'taxonomy'  => 'taxonomies'
        ];

    }

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
        $what = strtolower($args[0]);

        $where = $frc_framework->root_folders['post-types'][0] ?? false;

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

    /**
     * Migration tools
     *
     * ## OPTIONS
     *
     * <command-type>
     * : Commands that can be used. These are: new, up and down.
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
    public function migration ($args, $assoc_args) {
        $frc_framework = FRC\FRC::get_instace();

        $command = strtolower($args[0]);

        if(!in_array($command, ['create', 'up', 'down'])) {
            \WP_CLI::error("Unknown command. Commands are: create, up and down.");
        }

        $this->{"migration_" . $command}();
    }

    private function migration_create () {
        
    }

    private function migration_up () {

    }

    private function migration_down () {

    }
}

\WP_CLI::add_command("frc", new Framework());
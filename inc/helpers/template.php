<?php
namespace FRC;

function render($file, $data = [], $extract = false) {
    if(pathinfo($file, PATHINFO_EXTENSION) != 'php')
        $file .= '.php';

    return api_render(get_stylesheet_directory() . '/' . ltrim($file, '/'), $data, $extract);
}

function create_admin_page_table ($columns, $data, $context, $sortable_columns = [], $options = []) {
    ob_start();

    $options = array_replace_recursive($options, [
        'table_classes' => 'wp-list-table widefat fixed striped posts'
    ]);

    ?>
    <table class="<?= $options['table_classes']; ?>">
        <thead>
            <tr>
                <?php foreach($columns as $column_key => $column_value): ?>
                    <th>
                        <?= $column_value; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($data as $row): ?>
                <?php $row_id = array_values($row)[0]; ?>
                <tr id="<?= $context; ?>_<?= $row_id; ?>">
                    <?php foreach($row as $key => $value): ?>
                        <td class="title column-<?= $key; ?> has-row-actions column-primary page-<?= $key; ?>">
                            <?= $value; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
    <?php

    return ob_get_clean();
}

function comp_render ($file, $data = [], $extract = false) {
    global $frc_current_component_render_path;

    if(pathinfo($file, PATHINFO_EXTENSION) != 'php')
        $file .= '.php';

    return api_render($frc_current_component_render_path . '/' . ltrim($file, '/'), $data, $extract);
}

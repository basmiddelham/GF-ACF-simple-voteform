<?php

use Roots\Sage\Config;
use Roots\Sage\Container;

/**
 * Create ACF Options page
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page();
}

/**
 * Prepopulate Radio Buttons field
 * https://docs.gravityforms.com/gform_pre_render/#2-populate-choices-checkboxes
 */
add_filter('gform_pre_render', 'populate_radiobuttons');
add_filter('gform_pre_validation', 'populate_radiobuttons');
add_filter('gform_admin_pre_render', 'populate_radiobuttons');
add_filter('gform_pre_submission_filter', 'populate_radiobuttons');

function populate_radiobuttons($form)
{
    $formID = 1; // Form ID
    $fieldID = 3; // Field ID

    // Quit if not the correct form
    if ($form['id'] != $formID) {
       return $form;
    }

    // Get vote items from ACF Options
    $acfData = get_field('choices', 'options');
  
    // Create Radio Buttons with extra content in label field.
    foreach ($acfData as $data) {
        $items[] = array(
            'value' => $data['name'],
            'text' => wp_get_attachment_image($data['photo'], 'medium') . '<h3>' . $data['name'] . '</h3><p>' . $data['school']
        );
    }
    
    // Add Radio Buttons to to field
    foreach ($form['fields'] as &$field) {
        if ($field->id == $fieldID) {
            $field->choices = $items;
        }
    }
 
    return $form;
}

/**
 * Create Dashboard Widget
 */
add_action('wp_dashboard_setup', function () {
    global $wp_meta_boxes;
    wp_add_dashboard_widget('results_widget', 'NMT Resulaten', 'vote_results');
});

 /**
 * Add results to Dashboard Widget
 */
function vote_results()
{
    // Get total amount of entries
    $form_count  = RGFormsModel::get_form_counts(1);
    $entry_total = $form_count['total'];

    // Get all choices from ACF Options
    $acfData = get_field('choices', 'options');

    // Display a table with scores
    echo '<style>
            h3.vote_total { font-size: 18px!important; }
            .vote_results td { padding:8px 18px; }
            .vote_results thead td { 
                font-size: 14px; 
                font-weight: bold; 
                font-style: italic; 
                background-color: #0073aa; 
                color: white !important;
            }
            .vote_results tbody tr:nth-child(odd) { background-color: #fafafa; }
        </style>';
    echo '<h3 class="vote_total">Totaal aantal stemmen: ' . $entry_total . '</h3>';
    echo '<table class="widefat vote_results">';
    echo '<thead>';
    echo '<tr><td>Kandidaat</td><td>Stemmen</td></tr>';
    echo '</thead><tbody>';
    foreach ($acfData as $i => $data) {
        $search_criteria = array(
            'status'     => 'active', //optional
            'start_date' => $startdate, //optional
            'end_date'   => $enddate, //optional
            'field_filters' => array(
                array(
                    'key'   => $fieldID, // ID of the Choice field
                    'value' => $data['name'] // Name of the value to count
                )
            )
        );
        // Count the entries by a value: https://docs.gravityforms.com/api-functions/#count-entries
        $entry_count = GFAPI::count_entries($formID, $search_criteria);
        echo '<tr><td>' . $data['name'] . '</td><td>' . $entry_count . '</td></tr>';
    }
    echo '</tbody></table>';
}

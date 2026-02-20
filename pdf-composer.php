<?php
/**
 * Plugin Name: PDF Composer & Email sender for Elementor Pro Forms
 * Description: Composes a PDF from predefined pages based on Elementor Form submissions.
 * Version: 1.0.4
 * Text-domain: pdf-composer
 * Author: Július Sipos @ sipos.digital
 * Author URI: https://sipos.digital
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once('vendor/autoload.php');

define('PDFC_PATH', plugin_dir_path(__FILE__));
define('PDFC_URL', plugin_dir_url(__FILE__));

/**
 * Initiate Update Checker
 */
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/sipos-digital/pdf-composer',
    __FILE__,
    'pdf-composer'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

function register_new_form_actions( $form_actions_registrar ) {

    require_once( __DIR__ . '/generate_pdf.php' );
    $form_actions_registrar->register( new \Compose_PDF() );

}
add_action( 'elementor_pro/forms/actions/register', 'register_new_form_actions' );

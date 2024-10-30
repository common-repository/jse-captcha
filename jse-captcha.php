<?php
/**
 * Plugin Name: JSE Captcha
 * Plugin URI: https://jsecoin.com
 * Description: This plugin makes it easy to add the JSEcoin captcha to protect your comment forms from spam.
 * Version: 1.0.3
 * Author: mediacambridge
 * License: GPL2
 */
defined( 'ABSPATH' ) OR exit;

/** JSE Captcha Settings Page */
function jse_captcha_settings_page() {
?>
<div class="wrap">
<h2>JSE Captcha Wordpress Plugin</h2>
<p>The JSE wordpress captcha plugin makes it as easy to prevent spam by adding the JSE captcha to your website. Instead of clicking on bridges users are prompted to complete a short mini-game. To learn more about the JSE project please visit <a href="https://jsecoin.com" target="_blank">https://jsecoin.com</a></p>
<p>Where would you like the JSE captcha displayed?</p>
<form method="post" action="options.php" onsubmit="alert('Saved');">
    <?php settings_fields( 'jse-captcha-settings-group' ); ?>
    <?php do_settings_sections( 'jse-captcha-settings-group' ); ?>
    
    <input type="checkbox" name="jse-captcha-comments" value="1" <?php checked(1, get_option('jse-captcha-comments'), true); ?> /> Blog Comments
    <br>
    <input type="checkbox" name="jse-captcha-login" value="1" <?php checked(1, get_option('jse-captcha-login'), true); ?> /> Login Form
    <br>
    <input type="checkbox" name="jse-captcha-register" value="1" <?php checked(1, get_option('jse-captcha-register'), true); ?> /> Registration Form
    <br>
    <input type="checkbox" name="jse-captcha-contact" value="1" <?php checked(1, get_option('jse-captcha-contact'), true); ?> /> Contact Form 7
    <br>
    <?php submit_button(); ?>
</form>
</div>

<?php
}

/** Register settings */
function jse_captcha_settings() {
  add_option( 'jse-captcha-comments', "1");
  add_option( 'jse-captcha-login', "0");
  add_option( 'jse-captcha-register', "0");
  add_option( 'jse-captcha-contact', "0");
	register_setting( 'jse-captcha-settings-group', 'jse-captcha-comments' );
	register_setting( 'jse-captcha-settings-group', 'jse-captcha-login' );
	register_setting( 'jse-captcha-settings-group', 'jse-captcha-register' );
	register_setting( 'jse-captcha-settings-group', 'jse-captcha-contact' );
}

function jse_captcha_menu() {
	add_options_page('JSE Captcha Settings', 'JSE Captcha', 'administrator', 'jse-captcha-settings', 'jse_captcha_settings_page', 'dashicons-editor-unlink');
}

add_action('admin_init', 'jse_captcha_settings');
add_action('admin_menu', 'jse_captcha_menu');

function jse_captcha_get_code() {
  return '<div id="JSE-captcha"></div><script type="text/javascript" src="https://api.jsecoin.com/captcha/load/captcha.js"></script>';
}

/** Display JSE Captcha */
function jse_captcha_display_captcha() {
  $captcha_code = jse_captcha_get_code();
  echo $captcha_code;
}

/** Inject into form code (for contact form 7) */
function jse_captcha_return_with_captcha($form) {
  $captcha_code = jse_captcha_get_code();
  $form = str_replace('<input type="submit"', $captcha_code.'<input type="submit"', $form);
  return $form;
}

/** Get IP Address */
function jse_captcha_get_ip() {
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip=$_SERVER['HTTP_CLIENT_IP'];
  } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    $ip=$_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

/** Verify the captcha answer */
function jse_captcha_validate_captcha($commentdata) {
  if (is_user_logged_in()) return $commentdata;
	$ip = jse_captcha_get_ip();
  $request = wp_remote_get('https://api.jsecoin.com/captcha/check/' . $ip . '/');
  $response_body = wp_remote_retrieve_body( $request );
  $result = json_decode( $response_body, true );
	if ($result['pass'] == true) {
    return $commentdata;
  } else {
    // failed
    wp_delete_comment(absint($commentdata->comment_ID), true);
    wp_die( __('Human verification failed, please return and complete the captcha', 'captcha' ) );
  }
}

/** Inject actions on init */
function jse_captcha_inject() {
  $jse_captcha_comments = get_option('jse-captcha-comments');
  $jse_captcha_login = get_option('jse-captcha-login');
  $jse_captcha_contact = get_option('jse-captcha-contact');

  if ($jse_captcha_comments  == "1") {
    add_action('comment_form_after_fields', 'jse_captcha_display_captcha');
    add_filter('preprocess_comment', 'jse_captcha_validate_captcha');
  }
  if ($jse_captcha_login  == "1") {
    add_action('login_form', 'jse_captcha_display_captcha');
    add_action('wp_authenticate_user', 'jse_captcha_validate_captcha');
  }
  if ($jse_captcha_register  == "1") {
    add_action('register_form', 'jse_captcha_display_captcha');
    add_action('registration_errors', 'jse_captcha_validate_captcha');
  }
  if ($jse_captcha_contact  == "1") {
    add_filter('wpcf7_form_elements', 'jse_captcha_return_with_captcha');
    add_filter('wpcf7_validate', 'jse_captcha_validate_captcha');
  }
}

add_action('init', 'jse_captcha_inject');

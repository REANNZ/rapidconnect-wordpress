<?php
/**
 * Represents the view for the administration dashboard.
 *
 * @package   Rapid_Connect
 * @author    Bradley Beddoes <bradleybeddoes@aaf.edu.au>
 * @license   GPL-3.0
 * @link      http://rapid.aaf.edu.au
 * @copyright 2013 Australian Access Federation
 */
?>


<?php function rapid_connect_main_section_text() {
  echo '
    <p>To get up and running with AAF Rapid Connect you need to do a one time registration with the Australian Access Federation.</p>
    <p>You\'ll need the following details:</p>
    <ol>
            <li>The name of the Organisation who is registering this Wordpress instance;</li>
            <li>A descriptive name for your Wordpress site;</li>
            <li>The web URL which vistors enter to see Wordpress site; <br>
            <li>Your <strong>Callback URL</strong> as shown below;</li>
            <li>Your <strong>Secret</strong> as shown below (You MUST keep this secure. Do not share it with 3rd parties.);</li>
          </ol>
    <p>Finally decide if your Wordpress site will be used in a production or test capacity and do the online registration</p>
    <p><center><strong><a href="https://rapid.aaf.edu.au/registration">Register for Production</a></strong> | <a href="https://rapid.test.aaf.edu.au/registration">Register for Test</a></center></p>
  ';
} ?>

<?php function rapid_connect_url_section_text() {
  echo '
    <p>Once you have registered your Wordpress site you will need to enter below a unique Rapid Connect URL which is generated by AAF Rapid Connect especially for your Wordpress site. You obtain this in one of two ways:
    <ul>
      <li><strong>Production</strong>: The AAF will review your registration and provide your unique Rapid Connect URL over email within 48 hours. On receipt enter it below.</li>
      <li><strong>Test</strong>: The Rapid Connect URL is provided to you on the registration success page. Copy and paste it below.</li>
    </ul>
  ';
} ?>

<?php
  function rapid_connect_callback_markup() {
    $options = get_option('rapid_connect_options');
    echo "<input disabled size='60' type='text' value='".wp_login_url()."' />";
  }
?>

<?php
  function rapid_connect_secret_markup() {
    $options = get_option('rapid_connect_options');
    echo "<input id='secret' name='rapid_connect_options[secret]' size='60' type='text' value='{$options['secret']}' />";
  }
?>

<?php
  function rapid_connect_url_markup() {
    $options = get_option('rapid_connect_options');
    echo "<input id='url' name='rapid_connect_options[url]' size='60' type='text' value='{$options['url']}' />";
  }
?>

<div class="wrap">
  <?php screen_icon(); ?>
  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
  <?php } ?>

  <form action="options.php" method="post">
    <?php settings_fields('rapid_connect_options'); ?>
    <?php do_settings_sections('rapid_connect'); ?>

    <br><br>
    <input class="button button-primary button-large" name="Submit" type="submit" value="<?php esc_attr_e('Save Configuration'); ?>" />
  </form>

  <br><br>

  <h2>Ready to go</h2>
  <p>Having registered your service you can now login to your Wordpress site by clicking on the AAF button on your login screen.</p>
  <p>You will need to use your existing administrative account to give users who are logging in via AAF Rapid Connect rights to create and manage content.</p>

  <h3>Local Accounts</h3>
  <p>You can still use any of your existing local accounts, such as your administrative account, to login locally while also using Rapid Connect for other users.</p>
  <p>Be sure to keep these accounts secure.</p>
</div>

<?php
   define('DOING_AJAX', false);
   define('WP_ADMIN', false);
   require_once('../../../wp-load.php');
   require_once('../../../wp-includes/registration.php');
   @session_start();
   $error='';
   @header('Content-Type: text/html; charset=' . get_option('blog_charset'));
   wp_print_scripts('jquery');
   wp_print_styles('qixit');
   $_SESSION['AUTHOR_REGISTRATION']['wp_username'] = $_GET['user_login'];
   $_SESSION['AUTHOR_REGISTRATION']['email'] = $_GET['user_email'];
   $qixit_settings = get_option('qixit_settings');
   $qixit_purchase_object = new PurchaseFast();
   $qixit_purchase_object->set_qixit_pid($qixit_settings['qixit_admin_product_for_registration']);
?>
   <center>
      <iframe id="result" src="<?php echo $qixit_purchase_object->qixit_construct_url();?>" width="100%" height="200px" scrolling="no" frameborder="0px"  style="border: 0px"></iframe>
   </center>
</body>
</html>

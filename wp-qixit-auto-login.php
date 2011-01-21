<?php
define('DOING_AJAX', false);
define('WP_ADMIN', false);
require_once('../../../wp-load.php');
$error='';
@session_start();
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
?>
<form action="<?php echo get_option('siteurl').'/wp-login.php';?>"    method="post" name="autologin">
            <input type="hidden" name="log"   value="<?php echo $_SESSION['AUTO_LOGIN']['log'];?>" /> 
            <input type="hidden" name="pwd"   value="<?php echo $_SESSION['AUTO_LOGIN']['pwd'];?>" /> 
            <input type="hidden" name="redirect_to"   value="<?php echo admin_url();?>" /> 
            <input type="hidden" name="testcookie"   value="1" /> 
            <input name="wp-submit" type="submit" value="Log In" style="display:none" /> 
</form>
<? unset($_SESSION['AUTO_LOGIN']); ?>
<script>
  if(typeof document.autologin.submit=="function") 
  {
     document.autologin.submit();
  }
  else if(typeof document.autologin.submit.click=="function") 
  {
      document.autologin.submit.click();
  } 
  else 
  {
      document.autologin.submit();
  }
</script>
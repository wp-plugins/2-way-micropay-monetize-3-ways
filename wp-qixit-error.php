<?php
define('DOING_AJAX', false);
define('WP_ADMIN', false);
require_once('../../../wp-load.php');
$error='';
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
wp_print_scripts('jquery');
wp_print_styles('qixit');
?>
<center>
<?php 
   if (isset($_GET['message'])) 
   {
      echo __($_GET['message'],'qixit');
   }
?>
</center>

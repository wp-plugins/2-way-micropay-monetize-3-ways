<?php
define('DOING_AJAX', false);
@session_start();
require_once('../../../wp-load.php');
$error='';
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
wp_print_scripts('jquery');
wp_print_styles('qixit');
?>
<?php
   if (isset($_GET) && $_GET['post_id'] > 0)
   {
      $_SESSION['POST_ID']=$_GET['post_id'];
      $qixit_settings=get_option('qixit_settings');
      $qixit_purchase_object=new PurchaseFast();
      $qixit_purchase_object->set_qixit_pid($qixit_settings['qixit_admin_product_for_author_post_publish']);
?>
      <div id='qixit_product_purchase_login'>
         <center>
            <iframe id="result" src="<?php echo $qixit_purchase_object->qixit_construct_url();?>" width="100%" height="200" 
            scrolling="no" frameborder="0px" style="border: 0px">
            </iframe>
         </center>
      </div>
<?php 
   }
?>
</body>
</html>

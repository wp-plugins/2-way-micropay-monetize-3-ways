<?php
define('DOING_AJAX', false);
define('WP_ADMIN', false);
require_once('../../../wp-load.php');
$error='';

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
wp_print_scripts('jquery');
wp_print_styles('qixit');

$qixit_purchase_object=new PurchaseFast();

$qixit_ad_hoc_product = new QixitAdHocProduct($_GET['post_id']); 
if ($qixit_ad_hoc_product->get_qixit_PID()!='')
{
   $qixit_purchase_object->set_qixit_pid($qixit_ad_hoc_product->get_qixit_PID());
}
else
{
   $qixit_product = new QixitProduct($_GET['post_id']);
   $qixit_purchase_object->set_qixit_pid($qixit_product->get_post_qixit_PID());
}
?>
&nbsp;
<center>
   <iframe id="result" src="<?php echo $qixit_purchase_object->qixit_construct_url();?>" width="100%" height="200" scrolling="no"
   frameborder="0px" style="border: 0px"></iframe>
</center>

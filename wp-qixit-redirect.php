<?php
define('DOING_AJAX', false);
require_once('../../../wp-load.php');
@session_start();
require_once('../../../wp-includes/registration.php');
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
wp_print_scripts('jquery');


/**
 * Start o the code to filter query string 
 * Example string 
 * http://localhost/ALLWP/wordpress-300/wp-content/plugins/qixitwpp/wp-qixit-redirect.php?post_id=116&action=rerere&echo=qixit_id=jenny1988?
 * http://localhost/ALLWP/wordpress-300/wp-content/plugins/qixitwpp/wp-qixit-redirect.php?post_id=116&action=rerere&echo=qixit_id=jenny1988&ab=34?
 */
   $echo_value=explode('=',$_GET['echo']);
   for($i=0;$i<count($echo_value);$i++) 
   {   
      $_GET[$echo_value[$i]]=$echo_value[$i+1];
      $i++;
   }
   // This loop remove extra '?' from query_string
   foreach($_GET as $key => $value)
   {
      if ( strlen(stristr($_GET[$key],'?'))=='1' )
      {
         $_GET[$key]=substr($_GET[$key],0,(strlen($_GET[$key])-1));
      }
   }
   //print_r($_GET);
/*
// End of the code to filter query string 
*/


if ( isset( $_GET['action'] ) )
{
   if (strpos($_GET['action'],'?'))
   {
      $_GET['action']=substr($_GET['action'],0,(strlen($_GET['action'])-1));
   }
   else
   {
      $_GET['action']=$_GET['action'];
   }

   switch ( $action = trim($_GET['action']) )
   {
      case 'premium_comment' :
         if ( isset($_SESSION['QIXIT_POST_COMMENTS_DATA'])  )
         {
            $_SESSION['QIXIT_POST_COMMENTS_DATA']['qixit_id']=( isset($_GET['qixit_id']) )?$_GET['qixit_id']:'';
         }
?>
         <script>
            jQuery(document).ready(function($) 
            {  
               window.parent.parent.parent.location='<?php echo QIXIT_PLUGIN_URL."/wp-qixit-comment-post.php?post_id=".$_GET['post_id'];?>';
            });
         </script>
<?php
         die('&nbsp;');
         break;
            
      case 'registration' :
         // Author Registration
         global $wpdb, $wp_version;
         if ( !isset($_SESSION['AUTHOR_REGISTRATION']) )
         {
            wp_redirect(site_url());
            die('&nbsp;');
         }
         
         $qixit_settings = get_option('qixit_settings');

         // get the username and email
         $user_login = $_SESSION['AUTHOR_REGISTRATION']['wp_username'];
         $user_email = $_SESSION['AUTHOR_REGISTRATION']['email'];
         //$user_pass = wp_generate_password();
         $user_pass = $_SESSION['AUTHOR_REGISTRATION']['user_password'];
      
         // now create the user
         $user_id = wp_create_user( $user_login, $user_pass, $user_email );
      
         // insert data for author registration into qixit payment details
         $wpdb->insert( $wpdb->prefix.QIXIT_PAYMENT_DETAILS, array(
            'product_id' => 0, // we do not have a qixit product record for this type of QIXIT product
            'qixit_PID' => $qixit_settings['qixit_admin_product_for_registration'],
            'qixit_id' => $_GET['qixit_id'],
            'total' => $qixit_settings['cost_to_be_author'],
            'payment_for' => 'author_registration',
            'wp_user_id' => $user_id,
            'date_purchased' => date('Y-m-d H:i:s')
         ));  
         // insert data into author settings
         $wpdb->insert( $wpdb->prefix.QIXIT_AUTHOR_SETTINGS, array(
            'wp_user_id' => $user_id,
            'qixit_id' => $_GET['qixit_id'],
            'date_created' => date('Y-m-d H:i:s')
         ));
         // notify user
         wp_new_user_notification($user_id, $user_pass);
         unset($_SESSION['AUTHOR_REGISTRATION']);         
         $_SESSION['AUTO_LOGIN']['log']=$user_login;
         $_SESSION['AUTO_LOGIN']['pwd']=$user_pass;
         if (version_compare($wp_version, '3.0') >= 0)
         {
            update_user_meta( $user_id, 'wp_user_level','2');
            update_user_meta( $user_id, 'wp_capabilities',array('author'=>'1'));
         }
         else
         {
            update_usermeta( $user_id, 'wp_user_level','2');
            update_usermeta( $user_id, 'wp_capabilities',array('author'=>'1'));
         }
         ?>
         <script>
            jQuery(document).ready(function($) 
            {  
              window.parent.parent.parent.location='<?php echo QIXIT_PLUGIN_URL . "/wp-qixit-auto-login.php";?>';
            });
         </script>
         <?php
         die('&nbsp;');
         break;
      
      case 'post_publish' :         
         global $wpdb,$current_user;
         if ( !isset($_SESSION['POST_ID']) )
         {
            wp_redirect(site_url());
            die(" ");
         }
         $qixit_settings = get_option('qixit_settings');
         $qixit_product = new QixitProduct($_SESSION['POST_ID']);
         $wpdb->insert( $wpdb->prefix.QIXIT_PAYMENT_DETAILS, 
            array('product_id' => $qixit_product->get_product_id(), 
                  'qixit_PID' => $qixit_settings['qixit_admin_product_for_author_post_publish'],
                  'qixit_id' => $_GET['qixit_id'],
                  'total' => $qixit_settings['cost_to_publish_post_by_author'],
                  'payment_for' => 'add_post',
                  'date_purchased' => date('Y-m-d H:i:s')
         ));
         // insert/update product in qixit products
         qixit_author_post_product_add($_SESSION['POST_ID']);
         $author_info=qixit_get_author_settings($current_user->ID);
         if ( $author_info->qixit_id != $_GET['qixit_id'] ) 
         {   
            qixit_author_post_publish_wrong_creator_notification($_SESSION['POST_ID'],$_GET['qixit_id']);
         }
         else
         {
            qixit_author_post_publish_notification($_SESSION['POST_ID'],$_GET['qixit_id']);
         }
         $post = array();
         $post['ID'] = $_SESSION['POST_ID'];
         $post['post_status'] = 'publish';
         wp_update_post( $post );
         unset($_SESSION['POST_ID']);
         //close the popup window and take the user back to the post edit page
         echo "<script>
            jQuery(document).ready(function($) 
            { 
               window.parent.parent.parent.location='".admin_url('post.php?action=edit&post=' . $post['ID']) . "';
            });
         </script>";
         die('&nbsp;');
         break;
         
      default :
         die('&nbsp;');
         break;
   }// End of switch
}
else
{ 
?>
<script>
   jQuery(document).ready(function($) 
   {   
      $.ajax({
            type: "POST",
            url: "<?php echo QIXIT_PLUGIN_URL;?>/wp-qixit-ajax-req.php?action=post_viewed&post_id="+"<?php echo $_GET['post_id'];?>&qixit_id="+"<?php echo $_GET['qixit_id'];?>",
            dataType: "json",
            success: function(data){ 
                if (data['result']=='SUCCESS')
                {   
                   if (data['qixit_product_type']=='P')
                   {
                     window.parent.parent.parent.location='<?php echo get_permalink($_GET['post_id']);?>';
                   }
                   else if (data['qixit_product_type']=='A')
                   {   
           window.parent.parent.parent.location='<?php $meta = ''; $meta=get_post_meta($_GET['post_id'],'_qixit_delivery_url'); echo (is_array($meta) && !empty($meta))?$meta['0']:'';?>';
                   }
                }
                else if (data['result']=='WRONG_URL')
                {
                    alert('<?php echo __('Wrong Request Url','qixit');?>');
                }
                else if (data['result']=='WRONG_USER_DETAIL')
                {
                    alert('<?php echo __('Wrong username or password','qixit');?>');
                }
            }
         });
   });
   </script>
<?php
}
?>
</head><body>&nbsp;</body></html>
<?php
function qixit_author_post_product_add($post_id)
{
   global $wpdb;
   $post = get_post($post_id);
   if ($post->post_parent == 0)
   {
      $qixit_post_id = $post_id;
   }
   else
   {
      $qixit_post_id = $post->post_parent; 
   }

   $qixit_settings = get_option('qixit_settings');
   $user_info = get_userdata($post->post_author);
   $qixit_product = new QixitProduct($qixit_post_id);
   
   if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME || $qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW)
   {
      if ($qixit_product->get_premium_post_cost() == 0)
      {
         // no need to connect to QIXIT
         return;
      }
      $qixit_product_object = new ProductAdd();
      $author_info = qixit_get_author_settings($user_info->ID);         
      $qixit_product_object->set_vend($qixit_settings['qixit_id']);
      $password = base64_decode($qixit_settings['qixit_password']);
      $qixit_product_object->set_vendpw($password);
      $qixit_product_object->set_aff($author_info->qixit_id);
      $qixit_product_object->set_affpct($qixit_settings['percent_to_author']);

      $desc = trim($post->post_title)!=''?$post->post_title:$post_id;
      $qixit_product_object->set_desc($desc);
      $qixit_product_object->set_cost($qixit_product->get_premium_post_cost());
      $qixit_product_object->set_purl(get_option('siteurl'));
       
      if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
      {
         $qixit_product_object->set_perm('Y');
      }
      elseif ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW)
      {
         $qixit_product_object->set_perm('N');
      }
      $qixit_product_object->set_siteurl(get_option('siteurl'));
      $qixit_product_object->set_permalink(get_permalink($post_id));
      $qixit_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.Here\'s+the+link+if+you+want+to+see+' . $qixit_product_object->get_desc() .'+again..');
      
      $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='. $post_id);
      $qixit_product_object->set_echo('qixit_id=(userid)');
      $url=$qixit_product_object->construct_product_url();

      if (is_array($url))
      {
         $_SESSION['qixit_script_error_msg'] = $url['error_message'];
         return;
      }
      $html=@file_get_contents($url);
      $matches = explode("|", $html);
      if (is_array($matches))
      {
         $PID = trim(substr(strip_tags(nl2br($matches[3])),16));
         if ($PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1])))) == 'success') )
         {
            $wpdb->update( $wpdb->prefix.QIXIT_PRODUCTS,
            array( 'post_qixit_PID' => $PID,
                   'qixit_post_type' => $qixit_product->get_qixit_post_type(),
                   'premium_post_cost' => $qixit_product->get_premium_post_cost(),
                   'date_updated' => date('Y-m-d H:i:s')),
            array('post_id' => $post_id));
            // everything looks good, so lets return
            return;
         }
         //looks like we encountered an error
         $_SESSION['qixit_script_error_msg'] = qixit_get_qixit_system_error($matches);
         return;
      }
      else
      {
         $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
      }
   }
}
?>

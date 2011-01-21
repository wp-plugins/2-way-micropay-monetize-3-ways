<?php
define('DOING_AJAX', true);
require_once('../../../wp-load.php');
require_once('../../../wp-includes/registration.php');
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
if ( isset( $_GET['action'] ) )
{
   switch ( $action = $_GET['action'] )
   {
      case 'post_viewed' :
        @session_start();
         $qixit_settings = get_option('qixit_settings');
         $meta=get_post_meta($_GET['post_id'],'_qixit_delivery_url');
         if ( !empty($meta))
         {
            if (isset($_SESSION['QIXIT_PURCHASED_POST_ID'])) //To Stop Duplicate entry in payment table
            {
               unset($_SESSION['QIXIT_PURCHASED_POST_ID']);
               //This is Ad Hoc Product
               $qixit_ad_hoc_product = new QixitAdHocProduct($_GET['post_id']);
               // since its viewed, lets record the sale
               $wpdb->insert( $wpdb->prefix.QIXIT_PAYMENT_DETAILS, 
                  array('product_id' => $qixit_ad_hoc_product->get_product_id(),
                        'type' => 'A',
                        'qixit_PID' => $qixit_ad_hoc_product->get_qixit_PID(),
                        'qixit_id' => $_GET['qixit_id'],
                        'total' => $qixit_ad_hoc_product->get_cost(),
                        'payment_for' => 'view_ad_hoc',
                        'percent_to_author' => '',
                        'date_purchased' => date('Y-m-d H:i:s')
               ));
   
               // set cookie if the post is pay once view anytime
               if ($qixit_ad_hoc_product->get_qixit_ad_hoc_link_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
               {
                  if ( isset($_COOKIE['qixit_premium_pay_once_view_anytime_products']) )
                  {
                     $temp = $_COOKIE['qixit_premium_pay_once_view_anytime_products'];
                     $qixit_premium_pay_once_view_anytime_products = unserialize(stripslashes($temp));
                     $qixit_premium_pay_once_view_anytime_products[] = $_GET['post_id'];
                  }
                  else
                  {
                     $qixit_premium_pay_once_view_anytime_products = array();
                     $qixit_premium_pay_once_view_anytime_products[] = $_GET['post_id'];
                  }
                  setcookie('qixit_premium_pay_once_view_anytime_products' , serialize($qixit_premium_pay_once_view_anytime_products),
                  (time() + (($qixit_settings['view_any_time_cookie_exp']>0)?$qixit_settings['view_any_time_cookie_exp']:(30*24*60*6))), COOKIEPATH, COOKIE_DOMAIN);
                  
               }
               else
               {
                  $_SESSION['paid_for_one_time']='paid_for_one_time';
               }
            }
            echo $data='{"result" : "SUCCESS","qixit_product_type" : "A"}';
         }
         else
         {
            if (isset($_SESSION['QIXIT_PURCHASED_POST_ID'])) //To Stop Duplicate entry in payment table
            {
               unset($_SESSION['QIXIT_PURCHASED_POST_ID']);
               //This is post product
               $qixit_product = new QixitProduct($_GET['post_id']);
               // since its viewed, lets record the sale
               $wpdb->insert( $wpdb->prefix.QIXIT_PAYMENT_DETAILS, 
                  array('product_id' => $qixit_product->get_product_id(),
                        'qixit_PID' => $qixit_product->get_post_qixit_PID(),
                        'qixit_id' => $_GET['qixit_id'],
                        'total' => $qixit_product->get_premium_post_cost(),
                        'payment_for' => 'view_post',
                        'percent_to_author' => $qixit_product->get_percent_to_author(),
                        'date_purchased' => date('Y-m-d H:i:s')
               ));
               // set cookie if the post is pay once view anytime
               if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
               {
                  if ( isset($_COOKIE['qixit_premium_pay_once_view_anytime_products']) )
                  {
                     $temp = $_COOKIE['qixit_premium_pay_once_view_anytime_products'];
                     $qixit_premium_pay_once_view_anytime_products = unserialize(stripslashes($temp));
                     $qixit_premium_pay_once_view_anytime_products[] = $_GET['post_id'];
                  }
                  else
                  {
                     $qixit_premium_pay_once_view_anytime_products = array();
                     $qixit_premium_pay_once_view_anytime_products[] = $_GET['post_id'];
                  }
                  setcookie('qixit_premium_pay_once_view_anytime_products' , serialize($qixit_premium_pay_once_view_anytime_products),
                  (time() + (($qixit_settings['view_any_time_cookie_exp']>0)?$qixit_settings['view_any_time_cookie_exp']:(30*24*60*6))), COOKIEPATH, COOKIE_DOMAIN);
                  
               }
               else
               {
                  $_SESSION['paid_for_one_time']='paid_for_one_time';
               }
            }
            echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
         }
         die();
         break;

      case 'is_viewable' :
         @session_start();
         global $current_user;
         $post_id = ''; 
         if (isset($_GET['permalink']))
         {
            if (stristr(get_option('siteurl'),'https://'))
            {
               $protocal='https://';
            }
            else
            {
               $protocal='http://';
            }
            $post_id = url_to_postid($protocal.$_GET['permalink']);
         }
         
         if (isset($_GET['post_id']))
         {
            $post_id = $_GET['post_id'];
         }
         

         if ($post_id != '' && $post_id > 0)
         { 
            $post = get_post($post_id); 
            $qixit_product = new QixitProduct($post_id); 
            
            if ( $current_user->has_cap( "administrator" ) || $current_user->has_cap( "editor" ) )
            {   
               if ($qixit_product->get_post_qixit_PID() && $qixit_product->get_premium_post_cost() > 0)
               {
                  echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
                  return;
               }
               else
               {  $qixit_ad_hoc_product = new QixitAdHocProduct($post_id); 
                  if ( $qixit_ad_hoc_product->get_qixit_PID()!='' )
                  {
                     echo $data='{"result" : "SUCCESS","qixit_product_type" : "A", "durl" : "'.$qixit_ad_hoc_product->get_qixit_delivery_url().'"}';
                     return;
                  }
                  else
                  {
                     echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
                     return;
                  }
               }
            }
            
            if ($qixit_product->get_post_qixit_PID() && $qixit_product->get_premium_post_cost() > 0)
            {  
               if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
               {   
                  if (!(qixit_is_cookie_set($post_id)))
                  {   
                     $_SESSION['QIXIT_PURCHASED_POST_ID'] = $post_id;
                     echo $data='{"result" : "FAILED","post_id" : "'.$post_id.'","post_title" : "'.$post->post_title.'"}';
                     return;
                  }

                  echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
                  return;
               }
               elseif ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW)
               { 
                  if (!(isset($_SESSION['paid_for_one_time'])))
                  {
                     $_SESSION['QIXIT_PURCHASED_POST_ID'] = $post_id;
                     echo $data='{"result" : "FAILED","post_id" : "'.$post_id.'","post_title" : "'.$post->post_title.'"}';
                     return;
                  }
                  echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
                  return;
               }
               elseif ($qixit_product->get_qixit_post_type() == QIXIT_REG)
               {
               
                  echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
                  return;
               }
            }
            else
            { 
               $qixit_ad_hoc_product = new QixitAdHocProduct($post_id); 
               if ($qixit_ad_hoc_product->get_qixit_PID()!='')
               {   
                  if ($qixit_ad_hoc_product->get_qixit_ad_hoc_link_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
                  {   
                     if (!(qixit_is_cookie_set($post_id)))
                     {   
                        $_SESSION['QIXIT_PURCHASED_POST_ID'] = $post_id;
                        echo $data='{"result" : "FAILED","post_id" : "'.$post_id.'","post_title" : "'.$post->post_title.'"}';
                        return;
                     }
                     echo $data='{"result" : "SUCCESS","qixit_product_type" : "A", "durl" : "'.$qixit_ad_hoc_product->get_qixit_delivery_url().'"}';
                     return;
                  }
                  elseif ($qixit_ad_hoc_product->get_qixit_ad_hoc_link_type() == QIXIT_PREMIUM_PAY_PER_VIEW)
                  { 
                     if (!(isset($_SESSION['paid_for_one_time'])))
                     {
                        $_SESSION['QIXIT_PURCHASED_POST_ID'] = $post_id;
                        echo $data='{"result" : "FAILED","post_id" : "'.$post_id.'","post_title" : "'.$post->post_title.'"}';
                        return;
                     }
                     echo $data='{"result" : "SUCCESS","qixit_product_type" : "A", "durl" : "'.$qixit_ad_hoc_product->get_qixit_delivery_url().'"}';
                     return;
                  }
               }
               else
               {
                  echo $data='{"result" : "SUCCESS","qixit_product_type" : "A", "durl" : "'.$qixit_ad_hoc_product->get_qixit_delivery_url().'"}';
               }
            }
         }
         else
         {         
            echo $data='{"result" : "SUCCESS","qixit_product_type" : "P"}';
         }
         die();
         break;


   
      case 'session_marker_to_regular_comment' :
         @session_start();
         $_SESSION['QIXIT_POST_COMMENTS_DATA']['QIXIT_COMMENT_TYPE'] = QIXIT_REGULAR;
         echo $data='{"result" : "SUCCESS"}';
         die();
         break;

      default :
         die('0');
         break;
   }
}
?>
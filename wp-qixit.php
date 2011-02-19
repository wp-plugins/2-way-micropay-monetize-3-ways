<?php
/*
 Plugin Name: 2-Way Micropay - Monetize 3+ Ways 
 Plugin URI: http://2waymicropay.com/plugin/
 Description: Blog monetization using the Qixit's 2-Way Micropay platform. Instantly exchange pennies and dollars with equal ease--without the cost and in convenience of a credit card. Sell content, charge guest authors to post articles, or charge for premium placement of comments above the free comments.

 Author: QixIT & Nugget Solutions Inc
 Version: 1.0.1
 Author URI: http://www.qixit.com/

Copyright 2010-2011  Qix Information Technology LLC
 
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
( at your option ) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
 
*/

/* Qixit Plugin Constants */
if ( ! defined( 'QIXIT_PLUGIN_BASENAME' ) )
{
   define( 'QIXIT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'QIXIT_PLUGIN_NAME' ) )
{
   define( 'QIXIT_PLUGIN_NAME', trim( dirname( QIXIT_PLUGIN_BASENAME ), '/' ) );
}
if ( ! defined( 'QIXIT_PLUGIN_DIR' ) )
{
   define( 'QIXIT_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . QIXIT_PLUGIN_NAME );
}
if ( ! defined( 'QIXIT_PLUGIN_URL' ) )
{
   define( 'QIXIT_PLUGIN_URL', WP_PLUGIN_URL . '/' . QIXIT_PLUGIN_NAME );
}
if ( ! defined( 'QIXIT_VERSION' ) )
{
   define( 'QIXIT_VERSION',"1.0.1");
}

/* DB Tables constants */
if ( ! defined( 'QIXIT_PRODUCTS' ) )
{
   define( 'QIXIT_PRODUCTS','qixit_products');
}
if ( ! defined( 'QIXIT_PAYMENT_DETAILS' ) )
{
   define( 'QIXIT_PAYMENT_DETAILS','qixit_payment_details');
}
if ( ! defined( 'QIXIT_AUTHOR_SETTINGS' ) )
{
   define( 'QIXIT_AUTHOR_SETTINGS','qixit_author_settings');
}
if ( ! defined( 'QIXIT_AD_HOC_PRODUCTS' ) )
{
   define( 'QIXIT_AD_HOC_PRODUCTS','qixit_ad_hoc_products');
}


/* Other constants */
if ( (!extension_loaded('curl')) || (!extension_loaded('curl')) )
{
   define( 'QIXIT_USEABLE',false);
}
else
{
   define( 'QIXIT_USEABLE',true);
}
   
if ( ! defined( 'QIXIT_REG' ) )
{
   define( 'QIXIT_REG','REG');
}
if ( ! defined( 'QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME' ) )
{
   define( 'QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME','PREMIUM_PAY_ONCE_VIEW_ANYTIME');
}
if ( ! defined( 'QIXIT_PREMIUM_PAY_PER_VIEW' ) )
{
   define( 'QIXIT_PREMIUM_PAY_PER_VIEW','PREMIUM_PAY_PER_VIEW');
}
if ( ! defined( 'QIXIT_AFFPCT' ) )
{
   define( 'QIXIT_AFFPCT','2'); //Numeric Value only
}
if ( ! defined( 'QIXIT_REGULAR' ) )
{
   define( 'QIXIT_REGULAR','REGULAR');
}
if ( ! defined( 'QIXIT_PREMIUM' ) )
{
   define( 'QIXIT_PREMIUM','PREMIUM');
}
if ( ! defined( 'QIXIT_DEFAUL_COST' ) )
{
   define( 'QIXIT_DEFAUL_COST','0.01');
}
if ( ! defined( 'QIXIT_DEFAUL_CHARACTERS' ) )
{
   define( 'QIXIT_DEFAUL_CHARACTERS','500');
}
if ( ! defined( 'QIXIT_DEFAUL_PERCENT_TO_AUTHOR' ) )
{
   define( 'QIXIT_DEFAUL_PERCENT_TO_AUTHOR','0'); //between 1 to 100
}
if ( ! defined( 'QIXIT_DEFAUL_COOKIE' ) )
{
   define( 'QIXIT_DEFAUL_COOKIE','365'); //any positive integer value
}


if ( ! defined( 'QIXIT_MORE_LINK' ) )
{
   define( 'QIXIT_MORE_LINK','Read more');
}
if ( ! defined( 'QIXIT_LEARN_MORE_LINK' ) )
{
   define( 'QIXIT_LEARN_MORE_LINK','http://2waymicropay.com/plugin/');
}
if ( ! defined( 'QIXIT_FREE_MONEY_LINK' ) )
{
   define( 'QIXIT_FREE_MONEY_LINK','http://www.qixit.com/info/wpcomment.htm');
}
if ( ! defined( 'QIXIT_AUTHOR_TAG' ) )
{
   define( 'QIXIT_AUTHOR_TAG',"Guest Author's Stories");
}
if ( ! defined( 'QIXIT_MAX_BULK_ACTION' ) )
{
   define( 'QIXIT_MAX_BULK_ACTION',"10"); //Integer Value only
}
if ( ! defined( 'QIXIT_PLUGIN_LINK' ) )
{
   define( 'QIXIT_PLUGIN_LINK',"http://2waymicropay.com/plugin/"); //Integer Value only
}

// Initialize the plugin
add_action( 'init', 'qixit_initialization');
function qixit_initialization()
{   
   if (!qixit_is_compatible())
   {
      add_action('admin_notices','qixit_not_compatible');
   }
   wp_register_style('qixit', QIXIT_PLUGIN_URL.'/css/style.css', array(), QIXIT_VERSION, 'all' );
   wp_enqueue_style('qixit');
}

function qixit_is_compatible()
{
   global $wp_version;
   if (version_compare($wp_version, "2.8", ">="))
   {
      return true;
   }
   
   return false;
}

function qixit_not_compatible()
{
   echo '<div class="error"><p>';
   echo __('QIX IT 2-Way Micropay plugin requires Wordpress 2.8 or newer.','qixit');
   echo '<a href="http://codex.wordpress.org/Upgrading_Wordpress">';
   echo __(' Please update!','qixit');
   echo '</a></p></div>';
}

function qixit_install() 
{
   global $wpdb, $qixit_db_version, $wp_roles;
   $qixit_db_version = "1.0";
   
   if (qixit_is_compatible())
   {   
      update_option('users_can_register','1');
      
      $charset_collate = '';
      if ( $wpdb->has_cap( 'collation' ) )
      {
         if ( ! empty( $wpdb->charset ) )
         {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
         }
         if ( ! empty( $wpdb->collate ) )
         {
            $charset_collate .= " COLLATE $wpdb->collate";
         }
      }
      
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      
      $table_name = $wpdb->prefix . QIXIT_PRODUCTS;
      if($wpdb->get_var("show tables like '$table_name'") != $table_name)
      {
         $sql =  "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                     `product_id` bigint(20) NOT NULL AUTO_INCREMENT,
                     `post_id` bigint(20) DEFAULT NULL,
                     `post_qixit_PID` varchar(255) DEFAULT NULL,
                     `qixit_post_type` enum('" . QIXIT_REG . "','" . QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME . "','" . QIXIT_PREMIUM_PAY_PER_VIEW. "') NOT NULL DEFAULT '" . QIXIT_REG ."',
                     `premium_post_cost` double(15,4) DEFAULT NULL,
                     `premium_post_url` varchar(255) DEFAULT NULL,
                     `comments_qixit_PID` varchar(255) DEFAULT NULL,
                     `premium_comments_cost` double(15,4) DEFAULT NULL,
                     `percent_to_author` double( 15, 2 )  DEFAULT NULL,
                     `date_created` datetime DEFAULT NULL,
                     `date_updated` datetime DEFAULT NULL,
                     PRIMARY KEY  (`product_id`),
                     UNIQUE KEY `post_qixit_PID` (`post_qixit_PID`)
                ) ENGINE=MyISAM $charset_collate AUTO_INCREMENT=1;";
      
         dbDelta($sql);
      }
      
      $table_name = $wpdb->prefix . QIXIT_AD_HOC_PRODUCTS;
      if($wpdb->get_var("show tables like '$table_name'") != $table_name)
      {
         $sql =  "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                     `product_id` bigint(20) NOT NULL AUTO_INCREMENT,
                     `post_id` bigint(20) DEFAULT NULL,
                     `qixit_PID` varchar(255) DEFAULT NULL,
                     `cost` double(15,4) DEFAULT NULL,
                     `date_created` datetime DEFAULT NULL,
                     `date_updated` datetime DEFAULT NULL,
                     PRIMARY KEY  (`product_id`),
                     UNIQUE KEY `qixit_PID` (`qixit_PID`)
                ) ENGINE=MyISAM $charset_collate AUTO_INCREMENT=1;";
      
         dbDelta($sql);
      }
      
      $table_name = $wpdb->prefix . QIXIT_PAYMENT_DETAILS;
      if($wpdb->get_var("show tables like '$table_name'") != $table_name)
      {
         $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                            `payment_id` BIGINT NULL AUTO_INCREMENT,
                            `product_id` BIGINT NULL ,
                            `type` enum('P','A') NOT NULL DEFAULT 'P',
                            `qixit_PID` varchar(255) DEFAULT NULL,
                            `qixit_id` VARCHAR( 60 ) DEFAULT NULL,
                            `total` double( 15, 4 ) NOT NULL ,
                            `payment_for` ENUM( 'author_registration', 'add_post', 'view_post','view_ad_hoc', 'comments' ) NOT NULL ,
                            `wp_user_id` BIGINT  DEFAULT NULL ,
                            `percent_to_author` double( 15, 2 )  DEFAULT NULL,
                            `date_purchased` DATETIME NULL DEFAULT NULL,
                            PRIMARY KEY  (`payment_id`) 
                        ) ENGINE=MyISAM $charset_collate AUTO_INCREMENT=1;";
         dbDelta($sql);
      }
   
      $table_name = $wpdb->prefix . QIXIT_AUTHOR_SETTINGS;
      if($wpdb->get_var("show tables like '$table_name'") != $table_name)
      {   
         $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `wp_user_id` int(11) NOT NULL,
                    `qixit_id` varchar(255) DEFAULT NULL,
                    `date_created` datetime DEFAULT NULL,
                    `date_updated` datetime DEFAULT NULL,
                    PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM $charset_collate AUTO_INCREMENT=1;";
         
          dbDelta($sql);
      }
       
      // Add new column 'qixit_comment_type' to wp_comments table
      $qixit_wp_field_set = $wpdb->get_results("SHOW COLUMNS FROM " . $wpdb->comments);
      $wp_comments_fields = array();
      foreach($qixit_wp_field_set as $key=>$field_obj)
      {
         $wp_comments_fields[]=$field_obj->Field;
      }
      if (!(in_array('qixit_comment_type', $wp_comments_fields)))
      {
         $wpdb->query("ALTER TABLE " . $wpdb->comments . " ADD `qixit_comment_type` ENUM( '".QIXIT_REGULAR."', '".QIXIT_PREMIUM."' ) NOT NULL DEFAULT '".QIXIT_REGULAR."' AFTER `user_id`");
      }
      
      add_option("qixit_db_version", $qixit_db_version);
      
      $wp_roles->add_cap( "editor", "qixit_settings_for_author" );
      $wp_roles->add_cap( "author", "qixit_settings_for_author" );
   }
}

register_activation_hook( __FILE__, 'qixit_install' );

if (qixit_is_compatible())
{
   require_once QIXIT_PLUGIN_DIR . '/wp-qixit-functions.php';
   require_once QIXIT_PLUGIN_DIR . '/includes/wp-qixit-purchasefast.php';
   require_once QIXIT_PLUGIN_DIR . '/includes/wp-qixit-productadd.php';
   require_once QIXIT_PLUGIN_DIR . '/includes/wp-qixit-product.php';
   require_once QIXIT_PLUGIN_DIR . '/includes/wp-qixit-ad-hoc-product.php';
   
   if (is_admin())
   {
      require_once QIXIT_PLUGIN_DIR . '/wp-qixit-admin.php';
   }
   else
   {
      require_once QIXIT_PLUGIN_DIR . '/wp-qixit-front.php';
   }
}


function qixit_admin_url( $query = array() ) {
global $plugin_page;

if ( ! isset( $query['page'] ) )
$query['page'] = $plugin_page;

$path = 'admin.php';

if ( $query = build_query( $query ) )
$path .= '?' . $query;

$url = admin_url( $path );

return esc_url_raw( $url );
}

add_filter( 'plugin_action_links', 'qixit_plugin_action_links', 10, 2 );

function qixit_plugin_action_links($links, $file ) {
if ( $file != QIXIT_PLUGIN_BASENAME )
return $links;

$url = qixit_admin_url( array( 'page' => 'qixit_settings_for_admin' ) );

$settings_link = '<a href="' . esc_attr( $url ) . '">'
. esc_html( __( 'Settings', 'qixit_settings_for_admin' ) ) . '</a>';

array_unshift( $links, $settings_link );

return $links;
}

?>
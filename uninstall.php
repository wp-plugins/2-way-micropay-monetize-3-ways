<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
{
   exit();
}


function uninstall_qixit_registration_help_page_exists()
{
   global $wpdb;
   
   $help_page = $wpdb->get_row( $wpdb->prepare(" SELECT * FROM " . $wpdb->posts . " as page WHERE page.post_name='author-registration-help' && page.post_type='page'") );                                                   
   if ($help_page)
   {
      return $help_page->ID;
   }
   return false;
}


// remove constants which are used by install process
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
if ( ! defined( 'QIXIT_AUTHOR_TAG' ) )
{
   define( 'QIXIT_AUTHOR_TAG',"Guest Author's Stories");
}

/**
 * Delete wp_option entries, drop qixit tables, drop column
 */

global $wpdb, $wp_roles;

// remove capabilities
$wp_roles->remove_cap( "editor", "qixit_settings_for_author" );
$wp_roles->remove_cap( "author", "qixit_settings_for_author" );

// remove "We publish your artical" Wediget 
$sidebars_widgets=get_option('sidebars_widgets');
foreach($sidebars_widgets as $key=>$new_array)
{
   if ( $key == 'primary-widget-area' )
   {
      foreach($sidebars_widgets['primary-widget-area'] as $key2=>$value)
      {
         if ( $value == 'qixit_we_publish_widget' )
         {
            unset($sidebars_widgets['primary-widget-area'][$key2]);
         }
      }
   }
   if ( $key == 'wp_inactive_widgets' )
   {
      foreach($sidebars_widgets['wp_inactive_widgets'] as $key2=>$value)
      {
         if ( $value == 'qixit_we_publish_widget' )
         {
            unset($sidebars_widgets['wp_inactive_widgets'][$key2]);
         }
      }
   }
}
update_option('sidebars_widgets',$sidebars_widgets);



// remove author registratio help page
if ($help_page_id=uninstall_qixit_registration_help_page_exists())
{
   wp_delete_post( $help_page_id, true);
}   

// Start remove ad-hoc products
$qixit_products_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . " WHERE qixit_PID  IS NOT NULL OR 
                                                               qixit_PID  IS NULL ") );
if ( count( $qixit_products_post_ids ) > 0  ) 
{ 
   foreach (  $qixit_products_post_ids  as $key => $qixit_products_post ) 
   {  
     wp_delete_post( $qixit_products_post->post_id, true);
   } //endforeach
}
// End remove ad-hoc products

// remove author tags(terms) and it's relation
$r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE name='".addslashes(QIXIT_AUTHOR_TAG)."'"));
if (count($r_set)>0)
{
   $term_id=$r_set[0]->term_id;
   wp_delete_term($term_id,'post_tag');
}

// drop column added by the plugin
$wpdb->query("ALTER TABLE  " . $wpdb->comments . "  DROP `qixit_comment_type`");

//drop tables added by the plugin
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . QIXIT_PRODUCTS );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . QIXIT_PAYMENT_DETAILS );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . QIXIT_AUTHOR_SETTINGS );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . QIXIT_AD_HOC_PRODUCTS );

delete_option('qixit_db_version');
delete_option('qixit_settings');

?>
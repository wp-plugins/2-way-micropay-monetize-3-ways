<?php
function qixit_add_qixit_product($post, $data)
{
   global $wpdb;
   $data_array = $data;
   $qixit_settings = get_option('qixit_settings');
   $post_owner = new WP_User( $post->post_author );
   $percent_amount='0';
   if ( $post_owner->has_cap( "administrator" ) )
   {
      $percent_amount='0';
   }
   else
   {
      $percent_amount=$qixit_settings['percent_to_author'];
   }
   $data_array['date_created'] = date('Y-m-d H:i:s');
   $data_array['date_updated'] = date('Y-m-d H:i:s');
   if (isset($_POST['qixit_product_type']) && trim($_POST['qixit_product_type'])=='A')
   {   
      $wpdb->insert( $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS, $data_array ); 
      return new QixitAdHocProduct($data['post_id']);
   }
   else 
   {   
      $data_array['percent_to_author'] = $percent_amount;
      $wpdb->insert( $wpdb->prefix.QIXIT_PRODUCTS, $data_array ); 
      return new QixitProduct($data['post_id']);
   }
}

function qixit_update_qixit_product($post_id, $data)
{
   global $wpdb;
   $data_array = $data;
   $data_array['date_updated'] = date('Y-m-d H:i:s');

   if (isset($_POST['qixit_product_type']) && $_POST['qixit_product_type']=='A')
   {
         $wpdb->update( $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS, 
                  $data_array, 
                  array( 'post_id' => $post_id) );  
         return new QixitAdHocProduct($post_id);   
   }
   else
   {
      $wpdb->update( $wpdb->prefix.QIXIT_PRODUCTS, 
                  $data_array, 
                  array( 'post_id' => $post_id) );   
        
      return new QixitProduct($post_id);    
   } 
}

function qixit_delete_qixit_product($post_id)
{
   global $wpdb;

   if ( $post_id == '' )
   {
      return ;
   }
   
   $post=get_post($post_id);
   $qixit_product = new QixitProduct($post->ID);
   
   if ( $qixit_product->get_product_id()=='' ) 
   {   
      $qixit_ad_hoc_product = new QixitAdHocProduct($post->ID); 
      
      $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . "  
                                             WHERE product_id = '".$qixit_ad_hoc_product->get_product_id()."' and payment_for = %s and type = 'A' ", 'view_post' ));
      $wpdb->query( $wpdb->prepare("DELETE FROM " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . "  
                                             WHERE post_id  = '$post->ID' ") );
      return true;
   }
   else
   {   
      $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . "  WHERE product_id = '".$qixit_product->get_product_id()."' and type = 'P' " ));
      $wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix.QIXIT_PRODUCTS . "  WHERE post_id  = '$post->ID' " ) );
      return true;
   }
}

function qixit_get_payment_details($qixit_PID)
{
   global $wpdb;
   $qixit_product = $wpdb->get_row($wpdb->prepare("SELECT *  FROM ".$wpdb->prefix.QIXIT_PRODUCTS." WHERE post_id='$post_id' "));
   return $qixit_product;
}

function qixit_get_term_relationships_id()
{
   global $wpdb;
   $r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE name='".addslashes(QIXIT_AUTHOR_TAG)."'"));
   $r_set_term_taxonomy = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->term_taxonomy." WHERE term_id ='".$r_set[0]->term_id."'"));
   return $r_set_term_taxonomy[0]->term_taxonomy_id;
}

function qixit_post_type_title($qixit_post_type=null)
{
   $qixit_post_type_default = array(QIXIT_REG, QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME, QIXIT_PREMIUM_PAY_PER_VIEW);
   if ( $qixit_post_type == null || !in_array($qixit_post_type, $qixit_post_type_default) )
   {
      return $qixit_post_type;
   }
   elseif ( $qixit_post_type == QIXIT_REG )
   {
      return __('Regular','qixit');
   }
   elseif ( $qixit_post_type == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )
   {
      return __('Pay once view anytime','qixit');
   }
   elseif ( $qixit_post_type == QIXIT_PREMIUM_PAY_PER_VIEW )
   {
      return __('Pay per view','qixit');
   }
}

function qixit_admin_settings_found()
{
   $qixit_settings = get_option( 'qixit_settings' );
   if ( empty( $qixit_settings ) || count($qixit_settings) <= 0 )
   {
     return false;
   }
   return true;
}

function qixit_get_current_author_settings()
{
   global $wpdb, $current_user;
   if ( !$current_user->has_cap( "administrator" ) )
   {
      $author_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". $wpdb->prefix.QIXIT_AUTHOR_SETTINGS . " WHERE wp_user_id = '" . $current_user->ID . "'"));
      return $author_info;
   }
   return null;
}

function qixit_get_author_settings($user_id)
{
   global $wpdb;
   $user = new WP_User( $user_id );
   if ( !$user->has_cap( "administrator" ) )
   {
      $author_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM ". $wpdb->prefix.QIXIT_AUTHOR_SETTINGS . " WHERE wp_user_id = '" . $user_id . "'"));
      return $author_info;
   }
   return null;
   
}

function qixit_is_cookie_set($post_id)
{
   if ( isset($_COOKIE['qixit_premium_pay_once_view_anytime_products']) )
   {
      $temp = array();
      $qixit_premium_pay_once_view_anytime_products = array();
      $temp = $_COOKIE['qixit_premium_pay_once_view_anytime_products'];
      $qixit_premium_pay_once_view_anytime_products = unserialize(stripslashes($temp));
      if ( in_array($post_id,$qixit_premium_pay_once_view_anytime_products) )
      {
         return true;
      }
      return false;
   }
   return false;
}

function qixit_get_qixit_system_error($messages)
{
   $generic_error_msg = 'There was an error in connecting to the Qixit system.'; 
   $error_reason=trim(strip_tags(nl2br($messages[2])));
   if ( trim($error_reason) != '' )
   {
      return $error_reason;
   }
   return $generic_error_msg;
}

function qixit_set_old_status_post($post_id)
{
   global $wpdb, $qixit_post_old_status;
   if ( isset( $qixit_post_old_status ) && $qixit_post_old_status != '' )
   {   
      $post = array(); 
      $post['ID'] = $post_id;
      $post['post_status'] = $qixit_post_old_status;
      wp_update_post( $post );
   }
}
function qixit_set_save_draft_post($post_id)
{
   global $wpdb;
   $post = array(); 
   $post['ID'] = $post_id;
   $post['post_status'] = 'draft';
   wp_update_post( $post );
}

// localization
add_action( 'init', 'qixit_load_plugin_textdomain' );
function qixit_load_plugin_textdomain() 
{
   load_plugin_textdomain( 'qixit', false, QIXIT_PLUGIN_NAME.'/languages' );
}

//wp front head also called from wp-qixit-comments-box.php
add_action( 'wp_head', 'qixit_wp_head_include' );
function qixit_wp_head_include() 
{
   ?>
   <script>
      //<![CDATA[
         function show_hide(id)
         {
            jQuery(document).ready(function($) 
            {
             $('#'+id).toggle('blind');
            });
         }
      //]]>
   </script>
<?php
}

//wp_admin_head
add_action( 'admin_head', 'qixit_wp_admin_head_include');
function qixit_wp_admin_head_include()
{
?>
   <link   href="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/css/qixit_report_page.css" rel="stylesheet" type="text/css" media="all" />
   <link   href="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/css/qixit_report_table.css" rel="stylesheet" type="text/css" media="all" />
   <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/js/jquery.dataTables.js"></script>

   <link   href="<?php echo QIXIT_PLUGIN_URL;?>/js/color-picker/css/colorpicker.css" rel="stylesheet" type="text/css" media="all" />
   <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/color-picker/js/colorpicker.js"></script>
   <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/color-picker/js/eye.js"></script>
   <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/color-picker/js/utils.js"></script>
   <script>
   //<![CDATA[
      function qixit_post_type_title(qixit_post_type)
      {  if (qixit_post_type == '<?php echo QIXIT_REG;?>')
         {
             return "<?php echo __('Regular','qixit');?>";
         }
         else if (qixit_post_type == '<?php echo QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME;?>') 
         {
            return "<?php echo __('Pay once view anytime','qixit');?>";
         }
         else if (qixit_post_type == '<?php echo QIXIT_PREMIUM_PAY_PER_VIEW;?>') 
         {
            return "<?php echo __('Pay per view','qixit');?>";
         }
         else 
         {
            return qixit_post_type;
         }
      }
   //]]>
   </script>
<?php
}
?>
<?php
function qixit_is_ad_hoc_product($post_id=null)
{ 
   if ( $post_id == null )   return false;
   $meta = get_post_meta($post_id,'_qixit_delivery_url');   
   if ( empty($meta) === true )
   {   
      return false;
   }
   else
   {
      return true;
   }
}

add_filter('previous_post_link', 'qixit_navigation_adhoc_filter');
add_filter('next_post_link', 'qixit_navigation_adhoc_filter');
function qixit_navigation_adhoc_filter($link)
{
   global $post, $posts, $more;
   if ( $more && qixit_is_ad_hoc_product($post->ID) == true ) 
   {   
      return '';
   }
   else
   {
      return $link;
   }
}

add_filter('the_posts', 'adhoc_link_filter');
add_filter('the_posts', 'guest_stories_filter');
/**
 * This filter is using on both site. Front site as well admin site.
 */
function adhoc_link_filter($posts)
{ 
   //Conditon give Ad hoc products
   if ( (isset($_GET['page']) && $_GET['page'] == 'qixit_adHoc_list') || isset($_GET['adhoc']) )
   {  $new_post = array();
      foreach( $posts as $key => $post )
      { 
         $meta = get_post_meta($post->ID,'_qixit_delivery_url');
         if ( empty($meta) === true )
         {   
            unset($posts[$key]);
         }
      }
      foreach($posts as $key => $post)
      {
         $new_post[]=$post;
      }
      return $new_post;
   }
   elseif ( count($posts) > 1 ) //Conditon remvoe adHoc products from post list
   { 
      $new_post=array(); 
      foreach($posts as $key => $post)
      {  
         $meta=get_post_meta($post->ID,'_qixit_delivery_url');   
         if (!is_array($meta) && $meta == '') 
         {
            continue;
         }
         if ( count($meta) > 0 )
         {   
            unset($posts[$key]);
         }
      }
      foreach($posts as $key => $post)
      {
         $new_post[]=$post;
      }
      $posts=$new_post;
   }
   return $posts;
}
/**
 * This filter is using on both site. Front site as well admin site.
 */
function guest_stories_filter($posts)
{   
   $new_post = array();
   if ( isset($_GET['rgs']) )
   {
      foreach($posts as $key => $post)
      {
         $owner = new WP_User( $post->post_author );
         
         if ( $owner->has_cap( "administrator" ))
         {   
            unset($posts[$key]);
         }
      }
   }
   elseif ( (isset($_GET['page']) && $_GET['page'] == 'qixit_cross_ref_page') )
   {
      foreach($posts as $key => $post)
      {
         $owner = new WP_User( $post->post_author );
         
         if ( $owner->has_cap( "administrator" ))
         {   
            unset($posts[$key]);
         }
         else
         {   
            $qixit_product = new QixitProduct($post->ID);
            if ( $qixit_product->get_qixit_post_type() == QIXIT_REG )
            {
               unset($posts[$key]);
            }
         }
      }
   }
      foreach($posts as $key => $post)
      {
         $new_post[] = $post;
      }
      return $new_post;
}

function qixit_is_valid_url($url)
{
   return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function qixit_get_post_meta($post_id='')
{   
      $post_meta = array();
      if ( $post_id == '' )
      {
         return $post_meta;
      }
      
      $qixit_cost             = get_post_meta( $post_id, '_qixit_cost' );
      $qixit_pitch_url        = get_post_meta( $post_id, '_qixit_pitch_url' );
      $qixit_delivery_url     = get_post_meta( $post_id, '_qixit_delivery_url' );
      $qixit_ad_hoc_link_type = get_post_meta( $post_id, '_qixit_ad_hoc_link_type' );
      
      if ( empty($qixit_cost) )             $qixit_cost[]='';
      if ( empty($qixit_pitch_url) )        $qixit_pitch_url[]='';
      if ( empty($qixit_delivery_url) )     $qixit_delivery_url[]='';
      if ( empty($qixit_ad_hoc_link_type) ) $qixit_ad_hoc_link_type[]='';
      
      $post_meta['_qixit_cost']             = $qixit_cost[0];
      $post_meta['_qixit_pitch_url']        = $qixit_pitch_url[0];
      $post_meta['_qixit_delivery_url']     = $qixit_delivery_url[0];
      $post_meta['_qixit_ad_hoc_link_type'] = $qixit_ad_hoc_link_type[0];

      
      return (object)$post_meta;
}

function qixit_registration_help_page_exists()
{
   global $wpdb;
   
   $help_page = $wpdb->get_row( $wpdb->prepare(" SELECT * FROM " . $wpdb->posts . " as page WHERE page.post_name='author-registration-help' && page.post_type='page'") );                                                   
   if ($help_page)
   {
      return $help_page->ID;
   }
   return false;
}

function qixit_sales_history_data($task=false,$where='')
{
   global $wpdb,$current_user;
   $return_fetch_data=array();
   $total_amount='0';
   $total_amount_for_author='0';
   $total_amount_for_admin='0';
   $qixit_no_record_found = true;
   
   $sales_history_set = $wpdb->get_results( $wpdb->prepare(" SELECT *,date_purchased as payment_date,qp.percent_to_author as qixit_products_percent_to_author, pd.percent_to_author as qixit_payment_details_percent_to_author FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . " as pd 
                                              LEFT JOIN " . $wpdb->prefix.QIXIT_PRODUCTS . " as qp ON pd.product_id=qp.product_id 
                                              $where order by date_purchased desc ") );                                        
   if ( count($sales_history_set) > 0 ) 
   {
      if ( $task == 'echo' )
      {
         ?>
            <div id="sales_report_filter_dropdown">
            <?php echo __('Search Payments for ','qixit'); ?>
            <select onchange="window.location='<?php echo admin_url('admin.php?page=qixit_sales_history&f=');?>'+this.value" >
                  <option value=""   <?php echo ((isset($_GET['f']) && $_GET['f'] == '') || !isset($_GET['f']))?"selected='selected'":"";?> >
                  <?php echo __('All Payments','qixit');?></option>
                  <?php 
                  if ( $current_user->has_cap( "administrator" ) )
                  {
                  ?>
                  <option value="ar" <?php echo (isset($_GET['f']) && $_GET['f'] == 'ar')?"selected='selected'":"";?> ><?php echo __('Author Registration','qixit');?></option>
                  <option value="ap" <?php echo (isset($_GET['f']) && $_GET['f'] == 'ap')?"selected='selected'":"";?> ><?php echo __('Post publish','qixit');?></option>
                  <option value="vadhoc" <?php echo (isset($_GET['f']) && $_GET['f'] == 'vadhoc')?"selected='selected'":"";?> ><?php echo __('Ad hoc Viewed','qixit');?></option>
                  <?php   }  ?>
                  <option value="vp" <?php echo (isset($_GET['f']) && $_GET['f'] == 'vp')?"selected='selected'":"";?> ><?php echo __('Post Viewed','qixit');?></option>
                  <option value="c"  <?php echo (isset($_GET['f']) && $_GET['f'] == 'c')?"selected='selected'":"";?> ><?php echo __('Comment','qixit');?></option>
            </select>
            </div>
<div id="disclaimer22">
<p style="width: 700px;" >
These amounts do not include fees or refunds.<br>  The account history provided by Qixit is the official record.
</div>
            <div id="sales_report_export_csv_button" >
            <a href="<?php echo admin_url('admin.php?page=qixit_sales_history&export=csv'.((isset($_GET['f']))?'&f='.$_GET['f']:'') );?>" class="qixit_free_comment_submit"  >
                  <span class="qixit_free_comment_submit_span"><?php echo __('Export to CSV','qixit');?></span>
            </a>
            </div>

         <table class="widefat"  id="sales_report" >
         <thead >
         <tr >
            <th scope="col" style="text-align:center;width:10%;cursor:pointer;'"><?php echo __('Paid For','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:center;width:5%;cursor:pointer"><?php echo __('Amount Paid','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:left;width:10%;cursor:pointer"><?php echo __('Date','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:left;width:10%;cursor:pointer"><?php echo __('Buyer&rsquo;s Qixit User ID','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:center;width:5%;cursor:pointer"><?php echo __('Qixit Transaction ID','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:left;width:15%;cursor:pointer"><?php echo __('Product Title','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:left;width:20%;cursor:pointer"><?php echo __('Amount Sharing','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:center;width:10%;cursor:pointer"><?php echo __('Author&rsquo;s percent share','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
            <th scope="col" style="text-align:left;width:15%;cursor:pointer"><?php echo __('Author','qixit');?>
            <img class="sort_asc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_asc.png" />
            <img class="sort_desc_img" src="<?php echo QIXIT_PLUGIN_URL;?>/js/datatables/media/images/sort_desc.png" />
            </th>
         </tr>
         </thead>
         
         <tbody>
       <?php
      }

      
      if ( $current_user->has_cap( "administrator" ) )
      {
         // Author Registration Report
         foreach (  $sales_history_set  as $key => $sales_history ) 
         {            
            if ( $sales_history->payment_for == 'author_registration' ) 
            {   $owner = new WP_User( $sales_history->wp_user_id );
                $qixit_no_record_found = false;
               //Total Amount
               $total_amount+=$sales_history->total;
               if ( $task == 'echo' )
               {
                  ?>
                  <tr valign="top"  >
                     <td class="td_left"><?php echo __('Author registration','qixit');?></td>
                     <td class="td_center"><?php echo ($sales_history->total)*100/100;?>
                     </td>
                     <td class="td_left"><?php echo mysql2date(get_option('date_format'), $sales_history->payment_date); ?></td>
                     <td class="td_left"><?php echo $sales_history->qixit_id;?></td>
                     <td class="td_center"><?php echo $sales_history->payment_id;?></td>
                     <td class="td_left">&nbsp;</td>
                     <td class="td_left">&nbsp;</td>
                     <td class="td_left">&nbsp;</td>
                     <td class="td_left"><?php if(isset($owner->display_name)) echo $owner->display_name;?></td>
                  </tr>
                  <?php

               }
               
               if ( $task == 'fetch' )
               {
                  $return_fetch_data[$key][] = __('Author registration','qixit');
                  $return_fetch_data[$key][] = ($sales_history->total)*100/100;
                  $return_fetch_data[$key][] =  mysql2date(get_option('date_format'), $sales_history->payment_date); 
                  $return_fetch_data[$key][] = $sales_history->qixit_id;
                  $return_fetch_data[$key][] = $sales_history->payment_id;
                  $return_fetch_data[$key][] = '';
                  $return_fetch_data[$key][] = '';
                  $return_fetch_data[$key][] = '';
                  $return_fetch_data[$key][] = $owner->display_name;
               }
            }
         } //endforeach
         
         // Add Post (Post Publish) 
         foreach (  $sales_history_set  as $key => $sales_history ) 
         {               
            if ( $sales_history->payment_for == 'add_post' ) 
            {  
               $post  = get_post( $sales_history->post_id );
               if (is_null($post))
               {
                  continue;
               }
               $owner = new WP_User( $post->post_author );
               $qixit_no_record_found = false;
               
               // Total Amount
               $total_amount+=$sales_history->total;
               
               if ( $task == 'echo' )
               {
                  ?>
                  <tr valign="top"  >
                     <td class="td_left" >
                     <?php
                        if ( $sales_history->payment_for == 'add_post' ) 
                        {
                           echo __('Post publish','qixit');
                        }
                        if ( $sales_history->payment_for == 'comments' ) 
                        {
                           echo __('Comment','qixit');
                        }
                     ?>
                     </td>
                     <td class="td_center" ><?php echo ($sales_history->total)*100/100;?></td>
                     <td class="td_left"><?php echo mysql2date(get_option('date_format'), $sales_history->payment_date); ?></td>
                     <td class="td_left"><?php echo $sales_history->qixit_id;?></td>
                     <td class="td_center"><?php echo $sales_history->payment_id;?></td>
                     <td class="td_left"><a href="<?php echo get_permalink($post->ID);?>"><?php echo $post->post_title;?></a></td>
                     <td class="td_left">&nbsp;</td>
                     <td class="td_left">&nbsp;</td>
                     <td class="td_left" ><?php echo $owner->display_name;?></td>
                  </tr>
                  <?php
               }
               
               if ( $task == 'fetch' )
               {
                  if ( $sales_history->payment_for == 'add_post' ) 
                  {
                     $return_fetch_data[$key][] = __('Post publish','qixit');
                  }
                  if ( $sales_history->payment_for == 'comments' ) 
                  {
                     $return_fetch_data[$key][] = __('Comment','qixit');
                  }
                  $return_fetch_data[$key][] = ($sales_history->total)*100/100;
                  $return_fetch_data[$key][] = mysql2date(get_option('date_format'), $sales_history->payment_date); 
                  $return_fetch_data[$key][] = $sales_history->qixit_id;
                  $return_fetch_data[$key][] = $sales_history->payment_id;
                  $return_fetch_data[$key][] = $post->post_title;
                  $return_fetch_data[$key][] = '';
                  $return_fetch_data[$key][] = '';
                  $return_fetch_data[$key][] = $owner->display_name;
               }
            }
         } //endforeach   
         
      } //if ( $current_user->has_cap( "administrator" ) ) For Administrators olnly               
                
               
      // Comment on post
      foreach (  $sales_history_set  as $key => $sales_history ) 
      {               
         if ( $sales_history->payment_for == 'comments' ) 
         {  
            $post  = get_post( $sales_history->post_id );
            if (is_null($post))
            {
               continue;
            }
         
            $owner = new WP_User( $post->post_author );
            if ( !$current_user->has_cap( "administrator" ) )
            {
               if ( $current_user->ID != $post->post_author )
               continue;
            } 
            $qixit_no_record_found = false;
            //
            //
            //Total Amount
            $total_amount+=$sales_history->total;
            //Total Amount sharing
            if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
            {  
               $percent_to_author=$sales_history->total/100*$sales_history->qixit_payment_details_percent_to_author;
               $total_amount_for_author+=$percent_to_author;
               $total_amount_for_admin+=$sales_history->total-$percent_to_author;
            }
            //
            //
            if ( $task == 'echo' )
            {
               ?>
               <tr valign="top"  >
                  <td class="td_left" >
                  <?php
                     if ( $sales_history->payment_for == 'add_post' ) 
                     {
                        echo __('Post publish','qixit');
                     }
                     if ( $sales_history->payment_for == 'comments' ) 
                     {
                        echo __('Comment','qixit');
                     }
                  ?>
                  </td>
                  <td class="td_center" ><?php echo ($sales_history->total)*100/100;?>
                  </td>
                  <td class="td_left"><?php echo mysql2date(get_option('date_format'), $sales_history->payment_date); ?></td>
                  <td class="td_left"><?php echo $sales_history->qixit_id;?></td>
                  <td class="td_center"><?php echo $sales_history->payment_id;?></td>
                  <td class="td_left"><a href="<?php echo get_permalink($post->ID);?>"><?php echo $post->post_title;?></a></td>
                  <td class="td_left">
                  <?php 
                        if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                        {  
                           echo __('Author','qixit').':'.$percent_to_author.', '.__('Publisher:','qixit').($sales_history->total-$percent_to_author);
                        }
                        else
                        {
                           echo "&nbsp;";
                        }
                  ?>
                  </td>
                  <td class="td_center">
                  <?php 
                        if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                        {  
                           echo number_format($sales_history->qixit_payment_details_percent_to_author,2).'%';
                        }
                        else
                        {
                           echo "&nbsp;";
                        }
                  ?>
                  </td>
                  <td class="td_left" ><?php echo $owner->display_name;?></td>
                  </tr>
               <?php
            }
            
            if ( $task == 'fetch' )
            {
                  if ( $sales_history->payment_for == 'add_post' ) 
                  {
                     $return_fetch_data[$key][] = __('Post publish','qixit');
                  }
                  if ( $sales_history->payment_for == 'comments' ) 
                  {
                     $return_fetch_data[$key][] = __('Comment','qixit');
                  }
                  $return_fetch_data[$key][] = ($sales_history->total)*100/100;
                  $return_fetch_data[$key][] = mysql2date(get_option('date_format'), $sales_history->payment_date); 
                  $return_fetch_data[$key][] = $sales_history->qixit_id;
                  $return_fetch_data[$key][] = $sales_history->payment_id;
                  $return_fetch_data[$key][] = $post->post_title;
                  //sharing column
                  if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                  {  
                     $return_fetch_data[$key][] = __('Author','qixit').':'.$percent_to_author.', '.__('Publisher:','qixit').($sales_history->total-$percent_to_author);
                  }
                  else
                  {
                     $return_fetch_data[$key][] = "";
                  }
                  //percent column
                  if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                  {  
                     $return_fetch_data[$key][] = number_format($sales_history->qixit_payment_details_percent_to_author,2).'%';
                  }
                  else
                  {
                     $return_fetch_data[$key][] = '';
                  }
                  $return_fetch_data[$key][] = $owner->display_name;
            }
         }
      } //endforeach   

      // Post viewed 
      foreach (  $sales_history_set  as $key => $sales_history ) 
      {  
         if ( $sales_history->type == 'A' && $sales_history->payment_for == 'view_ad_hoc')
         {   
            $sales_ad_hoc_history = $wpdb->get_row( $wpdb->prepare(" SELECT * FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . " as pd 
                                                                      LEFT JOIN " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . " as a ON pd.product_id=a.product_id 
                                                                      WHERE a.qixit_PID='".$sales_history->qixit_PID."'") );
         }   

         if ( $sales_history->payment_for == 'view_post'  || $sales_history->payment_for == 'view_ad_hoc' ) 
         { 
            $post  = ( $sales_history->type == 'P' )?get_post($sales_history->post_id):get_post($sales_ad_hoc_history->post_id);
            $owner = new WP_User( $post->post_author );
            if ( !$current_user->has_cap( "administrator" ) )
            {
               if ( $current_user->ID != $post->post_author )
               continue;
            } 
            $qixit_no_record_found = false;
            //
            //
            //Total Amount
            $total_amount+=$sales_history->total;
            //Amount Sharing Caluclation
            if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
            {   
                 $percent_to_author=$sales_history->total/100*$sales_history->qixit_payment_details_percent_to_author;
                $total_amount_for_author+=$percent_to_author;
                $total_amount_for_admin+=$sales_history->total-$percent_to_author;
            }
            //
            //
            if ( $task == 'echo' )
            {
               ?>
               <tr valign="top"  >
                  <td class="td_left" ><?php echo ( $sales_history->type == 'P' )?__('Post viewed','qixit'):__('Ad hoc viewed','qixit'); ?></td>
                  <td class="td_center" ><?php echo ($sales_history->total)*100/100;?></td>
                  <td class="td_left"><?php echo mysql2date(get_option('date_format'), $sales_history->payment_date); ?></td>
                  <td class="td_left"><?php echo $sales_history->qixit_id;?></td>
                  <td class="td_center"><?php echo $sales_history->payment_id;?></td>
                  <td class="td_left">
                     <?php 
                        if ($sales_history->type == 'P')
                        {
                           ?>
                           <a href="<?php echo get_permalink($post->ID);?>"><?php echo $post->post_title;?></a>
                           <?php
                        }
                        else
                        {
                           ?>
                           <a href="<?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?>"><?php echo $post->post_title;?></a>
                           <?php
                        }   
                     ?>
                  </td>
                  <td class="td_left">
                  <?php if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                        {   
                            echo __('Author','qixit').':'.$percent_to_author.', '.__('Publisher:','qixit').($sales_history->total-$percent_to_author);
                        }
                        else
                        {
                           echo "&nbsp;";
                        }
                  ?>
                  </td>
                    <td class="td_center">
                     <?php 
                        if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                        {  
                           echo number_format($sales_history->qixit_payment_details_percent_to_author,2).'%';
                        }
                        else
                        {
                           echo "&nbsp;";
                        }
                     ?>
                  </td>
                  <td class="td_left" ><?php echo $owner->display_name;?></td>
               </tr>
               <?php
            }
            
            if ( $task == 'fetch' )
            {
                  $return_fetch_data[$key][] = ( $sales_history->type == 'P' )?__('Post viewed','qixit'):__('Ad hoc viewed','qixit');
                  $return_fetch_data[$key][] = ($sales_history->total)*100/100;
                  $return_fetch_data[$key][] = mysql2date(get_option('date_format'), $sales_history->payment_date); 
                  $return_fetch_data[$key][] = $sales_history->qixit_id;
                  $return_fetch_data[$key][] = $sales_history->payment_id;
                  $return_fetch_data[$key][] = $post->post_title;
                  //sharing column
                  if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                  {   
                      $return_fetch_data[$key][] =  __('Author','qixit').':'.$percent_to_author.', '.__('Publisher:','qixit').($sales_history->total-$percent_to_author);
                  }
                  else
                  {
                     $return_fetch_data[$key][] =  "";
                  }
                  //percent column
                  if ( $sales_history->qixit_payment_details_percent_to_author >= 0 ) 
                  {  
                     $return_fetch_data[$key][] =  number_format($sales_history->qixit_payment_details_percent_to_author,2).'%';
                  }
                  else
                  {
                     $return_fetch_data[$key][] =  "";
                  }
                 $return_fetch_data[$key][] = $owner->display_name;
            }
         }
      } //endforeach

      if ( $task == 'echo' )
      {
         ?>
         </tbody>
         <tfoot>
                  <tr valign="top"  >
                           <td class="td_left"><strong><?php echo __('Total Amount','qixit');?></strong></td>
                           <td class="td_center"><strong><?php echo $total_amount;?></strong></td>
                           <td class="td_left">&nbsp;</td>
                           <td class="td_left">&nbsp;</td>
                           <td class="td_center">&nbsp;</td>
                           <td class="td_left">&nbsp;</td>
                           <td class="td_left">
                           <strong>
                           <?php  echo __('Author','qixit').' : '.$total_amount_for_author; ?><br />
                           <?php  echo __('Publisher','qixit').' : '.($total_amount-$total_amount_for_author); ?>
                           </strong>
                           </td>
                           <td class="td_left">&nbsp;</td>
                           <td class="td_left">&nbsp;</td>
                 </tr>
          </tfoot>
         </table>
         <?php 
      }
      elseif ( $task == 'fetch' )
      {
         return $return_fetch_data;
      }
      else
      {
         return array(     'total_amount'                                 => $total_amount,
                           'total_amount_for_author'                     => $total_amount_for_author,
                           'total_amount_for_admin'                     => $total_amount_for_admin,
                           'grand_total_amount_for_admin'               => $total_amount-$total_amount_for_author,
                           'admin_total_for_registraiton_n_post_publish'=> $total_amount-($total_amount_for_author+$total_amount_for_admin)
                     );
      }
   }
   else
   {
      $qixit_no_record_found = true;
   }
   
   if ($qixit_no_record_found)
   {
      if ( $task == 'echo' )
      {
         ?>
         <table width="100%" >
               <tr>
                  <td ><?php echo __('No data found.','qixit');?></td>
               </tr>
         </table>
         <?php
      }
      else
      {
         return array();
      }
   }
}

function qixit_author_post_publish_notification($post_id,$qixit_id) 
{
   global  $current_user;
   $header     = 'Content-type: text/html; charset=iso-8859-1'.'\r\n';
   $post       = get_post( $post_id );
   $owner      = new WP_User( $post->post_author );

   $user_login = stripslashes($current_user->user_login);
   $user_email = stripslashes($current_user->user_email);

   $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
   
   $message  = '';
   $message  .= __('Dear Administrator','qixit') . "\r\n\r\n";
   $message  .= __('Congratulations, A post has been successfully published by author on','qixit') .' '.$blogname.'. '. "\r\n\r\n";
   $message  .= __('You can review the post at','qixit') .' <a href="'.get_permalink($post_id).'">'.get_permalink($post_id).'</a>. '. "\r\n\r\n";


   $message = str_replace( "\r\n", "<br/>", $message );
   @wp_mail(get_option('admin_email'), sprintf('[%s] '.__('Post published by author.','qixit'), $blogname), $message,$header);
   
   
   $message  = '';
   $message  .= __('Dear','qixit').' ' .$owner->display_name. "\r\n\r\n";
   $message  .= __('Congratulations, your post has been successfully published on','qixit') .$blogname.'. '. "\r\n\r\n";
   $message  .= __('You can review the post at','qixit') .'<a href="'.get_permalink($post_id).'">'.get_permalink($post_id).'</a>.'. "\r\n\r\n";
   
   
   $message = str_replace( "\r\n", "<br/>", $message );
   wp_mail($user_email, sprintf('[%s] '.__('Article published successfully.','qixit'), $blogname), $message,$header);

}
function qixit_author_post_publish_wrong_creator_notification($post_id,$qixit_id) 
{
   global $current_user;
   $header     = 'Content-type: text/html; charset=iso-8859-1'.'\r\n';
      
   $post       = get_post( $post_id );
   $owner      = new WP_User( $post->post_author );
   
   $user_login = stripslashes($current_user->user_login);
   $user_email = stripslashes($current_user->user_email);
   $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
   
   $message  = '';
   $message  .= __('Dear Administrator','qixit') . "\r\n\r\n";
   $message  .= __('This is to inform you that an article was published from an authors account using a different Qixit ID on','qixit') .' '.$blogname.'. '. "\r\n";
   $message  .= __('You can review the article at','qixit') .'<a href="'.get_permalink($post_id).'">'.get_permalink($post_id).'</a>.'. "\r\n\r\n";
   $message  .= __('Where qixit id is','qixit');               
   $message  .= ' '.$qixit_id."\r\n\r\n";

   
   $message = str_replace( "\r\n", "<br/>", $message );
   @wp_mail(get_option('admin_email'), sprintf('[%s] '.__('Post published from an Authors account by differnt qixit user','qixit'), $blogname), $message,$header);
   
   $message  = '';
   $message  .= __('Dear','qixit') .' '.$owner->display_name. "\r\n\r\n";
   $message  .= __('This is to inform you that an article was published from your account using a different Qixit ID. Please review the article','qixit').
                  ' <a href="'.get_permalink($post_id).'">'.get_permalink($post_id).'</a>. '."\r\n";   
   $message  .= __('and contact Qixit Support for any questions. Alternatively, you can modify your account settings to update your Qixit ID.','qixit');               
   
   $message = str_replace( "\r\n", "<br/>", $message );
   wp_mail($user_email, sprintf('[%s] '.__('Please review your account','qixit'), $blogname), $message,$header);
}
?>
<?php
/** *****************************************************************************
 *
 * Start Hooks Which are used by both part admin as well front.
 *
 ******************************************************************************* */

add_filter('get_next_post_join', 'qixit_adjesent_post_join');
add_filter('get_previous_post_join', 'qixit_adjesent_post_join');
function qixit_adjesent_post_join($join)
{
   global $wpdb;
   $join=$join." INNER JOIN ".$wpdb->prefix.QIXIT_PRODUCTS." AS q_p ON q_p.post_id     = p.ID ";   
   return $join;
}


add_filter('get_next_post_where', 'qixit_adjesent_post_where');
add_filter('get_previous_post_where', 'qixit_adjesent_post_where');
function qixit_adjesent_post_where($temp)
{
   //$temp=$temp." AND p.ping_status != 'closed' ";
   $temp=$temp." AND q_p.post_id  IS NOT NULL ";
   return $temp;
}


add_action( 'wp_meta', 'qixit_read_guest_stories' );
function qixit_read_guest_stories()
{
   echo '<li><a href="' . get_option('siteurl').'?rgs' . '" >' . __('Read Guest/Author&rsquo;s Stories') . '</a></li>';
}

/**
  * ********************** Start We publish widget functions ********************************************
  */
add_action('plugins_loaded','qixit_we_publish_widget_loaded');
function qixit_we_publish_widget($args)
{   
   $qixit_settings = get_option( 'qixit_settings' ); 
   //echo $qixit_settings['e_guest_authors'];
   extract($args); // extracts before_widget,before_title,after_title,after_widget
   if (empty($errors->errors) && $qixit_settings['cost_to_be_author']>0 && QIXIT_USEABLE )
   {
         echo $before_widget . $before_title . __((($qixit_settings['widget_name'])?$qixit_settings['widget_name']:'We Publish Your Articles'),'qixit') . $after_title . "<ul>";
         /*echo $before_widget . $before_title . __('We Publish Your Articles','qixit') . $after_title . "<ul>";*/
         if ($help_page_id=qixit_registration_help_page_exists())
         {
            echo '<li><a href="'.get_permalink($help_page_id).'" target="_blank">' . __('How This Works','qixit') . '</a></li>';
         }
         
         if ( get_option( 'users_can_register' )  && $qixit_settings['e_guest_authors'] == 1)
         {
            echo '<li><a href="' . site_url('wp-login.php?action=register&type=author', 'login') . '">' . __('Author Sign Up','qixit') . '</a></li>';
         }
         echo '<li>';
         //wp_loginout();
         if ( ! is_user_logged_in() )
         {
            echo $link = '<a href="' . esc_url( wp_login_url() ) . '">' . __('Author Log in','qixit') . '</a>';
         }
         else
         {
            echo $link = '<a href="' . esc_url( wp_logout_url() ) . '">' . __('Log out') . '</a>';
         }
         echo '</li>';
         echo "</ul>" . $after_widget;
   }
}
add_action( 'check_ajax_referer', 'we_publish_widget_form_submit');
function we_publish_widget_form_submit()
{
   @session_start();
   if ( isset($_POST) && isset($_POST['widget-id']) && $_POST['widget-id']==trim(strtolower('ID_of_qixit_we_publish_widget')) )
   {
      $options = get_option('qixit_settings');
      if ( is_array($options) && !empty($options) )
      {
         $_previous['qixit_settings_previous_cost_to_be_author']               = $_POST['qixit_settings_previous_cost_to_be_author'];
         $_previous['qixit_settings_previous_cost_to_publish_post_by_author']  = $_POST['qixit_settings_previous_cost_to_publish_post_by_author'];
         
         $submitted_options                         = $_POST['qixit_settings'];
         $options['cost_to_be_author']              = $submitted_options['cost_to_be_author'];
         $options['cost_to_publish_post_by_author'] = $submitted_options['cost_to_publish_post_by_author'];
         $options['post_cost_of_author']            = $submitted_options['post_cost_of_author'];
         $options['percent_to_author']              = $submitted_options['percent_to_author'];
         $options['widget_name']                    = ($submitted_options['widget_name'])?$submitted_options['widget_name']:'We Publish Your Articles';
         
         $error_msg = qixit_admin_options_for_authors_settings($options,$_previous);
         
         if (is_array($error_msg))
         {
            $_SESSION['qixit_widget_error'] = array();
            foreach($error_msg as $key => $error_message)
            {
               $_SESSION['qixit_widget_error'][$key] = $error_message;
            }            
            $_SESSION['qixit_widget_submitted_options'] = $submitted_options;
         }
         else
         {     $options = get_option('qixit_settings');//Donot remove this line we need fresh and updated data
               $options['cost_to_be_author']              = $submitted_options['cost_to_be_author'];
               $options['cost_to_publish_post_by_author'] = $submitted_options['cost_to_publish_post_by_author'];
               $options['post_cost_of_author']            = $submitted_options['post_cost_of_author'];
               $options['percent_to_author']              = $submitted_options['percent_to_author'];
               $options['widget_name']                    = ($submitted_options['widget_name'])?$submitted_options['widget_name']:'We Publish Your Articles';
               update_option('qixit_settings',$options);
               $_SESSION['qixit_widget_success'] = __('Settings saved.','qixit');
         }
      }
   }
}
function we_publish_widget_form()
{
      @session_start();
   
      if ( isset($_SESSION['qixit_widget_error']) && !empty($_SESSION['qixit_widget_error']) )
      {   
         qixit_show_errors($_SESSION['qixit_widget_error'],'<br/>');
         $qixit_settings            = get_option('qixit_settings');
         $options                   = $_SESSION['qixit_widget_submitted_options'];   
         $options['qixit_id']       = $qixit_settings['qixit_id'];
         $options['qixit_password'] = $qixit_settings['qixit_password'];
      }
      elseif ( isset($_SESSION['qixit_widget_success']) && !empty($_SESSION['qixit_widget_success']) )
      {
         qixit_show_success($_SESSION['qixit_widget_success']);   
         $options = get_option('qixit_settings');      
      }
      else
      {
         $options = get_option('qixit_settings');      
      }
      
      if ( is_array($options) && $options['qixit_id'] != '' && $options['qixit_password'] != '' )
      {   
         ?>  
         <p>
         <label for="qixit_settings[cost_to_be_author]"><?php echo __('Title','qixit');?> :</label>
         <input class="widefat" type="text" size="10" id="qixit_settings[widget_name]" name="qixit_settings[widget_name]" 
                     value="<?php echo $options['widget_name']; ?>" /> 
         </p>
             
         <p>
         <label for="qixit_settings[cost_to_be_author]"><?php echo __('Readers may setup an author account for a price of','qixit');?> :</label>
         <input class="widefat" type="text" size="10" id="qixit_settings[cost_to_be_author]" name="qixit_settings[cost_to_be_author]" 
                     value="<?php echo (is_array($options))?$options['cost_to_be_author']:QIXIT_DEFAUL_COST; ?>" /> 
         <input class="widefat" type="hidden" id="qixit_settings_previous_cost_to_be_author" name="qixit_settings_previous_cost_to_be_author" 
                     value="<?php echo (is_array($options))?$options['cost_to_be_author']:QIXIT_DEFAUL_COST; ?>" />
         </p>
         
         <p>
         <label for="qixit_settings[cost_to_publish_post_by_author]"><?php echo __('Authors may publish an article for a price of','qixit');?> :</label>
         <input class="widefat" type="text" size="10" id="qixit_settings[cost_to_publish_post_by_author]" name="qixit_settings[cost_to_publish_post_by_author]" value="<?php echo (is_array($options))?$options['cost_to_publish_post_by_author']:QIXIT_DEFAUL_COST; ?>"  /> 
         <input class="widefat" type="hidden" id="qixit_settings[cost_to_publish_post_by_author]" name="qixit_settings_previous_cost_to_publish_post_by_author" value="<?php echo (is_array($options))?$options['cost_to_publish_post_by_author']:QIXIT_DEFAUL_COST; ?>"  />
         </p>
         
         <p>
         <label for="qixit_settings[post_cost_of_author]"><?php echo __('Enable premium content, with a default price of','qixit');?> :</label>
         <input class="widefat" type="text" size="10" id="qixit_settings[post_cost_of_author]" name="qixit_settings[post_cost_of_author]" value="<?php echo (is_array($options))?$options['post_cost_of_author']:QIXIT_DEFAUL_COST; ?>" /> 
         </p>
         
         <p>
         <label for="qixit_settings[post_cost_of_author]"><?php echo __("Author&rsquo;s percent share",'qixit');?> :</label>
         <br/><small><?php echo __("(do not put a % sign, so 50% share will only be 50)",'qixit');?></small>
         <input class="widefat" type="text" size="10" id="qixit_settings[percent_to_author]" name="qixit_settings[percent_to_author]" value="<?php echo (is_array($options))?$options['percent_to_author']:QIXIT_DEFAUL_PERCENT_TO_AUTHOR; ?>" /> 
         
         </p>
         
         <?php
      }
      else
      {
         echo __("To active this widget you should first set admin options.",'qixit');
         echo "<br/>";
         echo '<a href=' . admin_url('options-general.php?page=qixit_settings_for_admin') . '>';
         echo __('Take me to QIXIT settings page.','qixit');
         echo '</a>';
         
         return 'noform';
      }
}
function qixit_we_publish_widget_loaded()
{   
   global $wp_version;
   $qixit_settings            = get_option('qixit_settings');
   $widget_ops = array('classname' => 'qixit_we_publish_widget', 'description' => "A very cool widget for author registration on sidebar." );
   wp_register_sidebar_widget('ID_of_qixit_we_publish_widget', (($qixit_settings['widget_name'])?$qixit_settings['widget_name']:'We Publish Your Articles') , 'qixit_we_publish_widget', $widget_ops);
   wp_register_widget_control('ID_of_qixit_we_publish_widget','qixit_we_publish_widget','we_publish_widget_form');
}


function qixit_all_settings_details(){
	$qixit_settings = get_option( 'qixit_settings' );
	return 	$qixit_settings;
}

//to get all data for a comment
function get_comments_data($id = ''){
   global $wpdb,$current_user; 
   $comments_data = array();
   $comments_data = $wpdb->get_results( $wpdb->prepare(" select `qixit_comment_type` from  `".$wpdb->prefix."comments` where `comment_ID` = '".$id."' ") );  	
   $ret = $comments_data[0]->qixit_comment_type;
   return $ret;
}


/************************* End We publish widget functions ************************/
/**********************************************************************************
 *
 * * * * * * End Hooks Which are used by both part admin as well front.* * * * * * 
 *
**********************************************************************************/
?>
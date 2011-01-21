<?php
add_action('admin_notices','verify_qixit_settings');
function verify_qixit_settings()
{
   global $current_user;
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE)
   {
      //verify administrator settings
      if ( $current_user->has_cap( "administrator" ) || $current_user->has_cap( "editor" ) )
      {   
         //verify administrator settings
         $qixit_settings = get_option( 'qixit_settings' );
         if ( empty( $qixit_settings ) || count($qixit_settings) <= 0 )
         {
            echo '<div class="error"><p>';
            echo __('Notice:QIXIT 2-Way Micropay plugin is almost ready to use. Please complete the QIXIT settings for it to work.','qixit');
            echo '<br /><a href=' . admin_url('options-general.php?page=qixit_settings_for_admin') . '>';
            echo __('Take me to QIXIT settings page.','qixit');
            echo '</a></p></div>';
         }
      }
      else
      {
         $user_info = qixit_get_current_author_settings(); 
         if ( ($user_info == '') || ( $user_info && ($user_info->qixit_id == '') ) )
         {
            echo '<div class="error"><p>';
            echo __('Notice:QIXIT 2-Way Micropay plugin is almost ready to use. Please complete the QIXIT settings for it to work.','qixit');
            echo '<br /><a href=' .  admin_url('profile.php?page=qixit_settings_for_author') . '>';
            echo __('Take me to QIXIT settings page.','qixit');
            echo '</a></p></div>';
         }
      }
   }
   
   if (!extension_loaded('curl'))
   {
         echo '<div class="error"><p>';
         echo __('Notice:QIXIT 2-Way Micropay plugin is almost ready to use. Please Enable the Curl settings for it to work.','qixit');
         echo '</p></div>';
   }
   if (!extension_loaded('openssl'))
   {
         echo '<div class="error"><p>';
         echo __('Notice:QIXIT 2-Way Micropay plugin is almost ready to use. Please Enable the Open SSL settings for it to work.','qixit');
         echo '</p></div>';
   }
}
?>
<?php
/**
 * add/update Qixit product for author registration for the option - "Readers may setup Guest Author account for a price"
 */
function qixit_product_for_author_registration($cost, $blogname,$qixit_id='',$qixit_password='')
{   

   if ( $cost <= 0 )
   {
      return '';
   }
   @session_start();
   $qixit_settings = get_option('qixit_settings');
   $qixit_product_object = new ProductAdd();
   if ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_registration',$qixit_settings) &&  $qixit_settings['qixit_admin_product_for_registration'] != '' )
   {
      //has PID, so its an update
      $qixit_product_object->set_qixit_pid($qixit_settings['qixit_admin_product_for_registration']);
   }

	if ( $qixit_id!='' && $qixit_password!='' ) 
	{	
		$qixit_product_object->set_vend($qixit_id);
	   $password = base64_decode($qixit_password);	
	}
	else
	{
		$qixit_product_object->set_vend($qixit_settings['qixit_id']);
	   $password = base64_decode($qixit_settings['qixit_password']);
	}
   $qixit_product_object->set_vendpw($password);
   $qixit_product_object->set_desc('Author+Registration+' . $blogname);
   $qixit_product_object->set_cost($cost);
   $qixit_product_object->set_aff($qixit_settings['qixit_id']);
   $qixit_product_object->set_affpct(QIXIT_AFFPCT);
   $qixit_product_object->set_purl(get_option('siteurl'));
   $qixit_product_object->set_perm('N');

   $qixit_product_object->set_rmsg('Thanks+for+registration+on+' . get_option('siteurl'));

//Below used Url Encoding for < = %3C and > = %3E
//$qixit_product_object->set_rmsg('Thanks+for+registration+on+%3Ca+href="'.get_option('siteurl').'"+target="_blank"%3E'.(str_replace(" ","+",get_option('blogname'))).'%3C/a%3E');

//Below used HTML encoding
//$qixit_product_object->set_rmsg('Thanks+for+registration+on+&lt;a+href="'.get_option('siteurl').'"+target="_blank"&gt;'.(str_replace(" ","+",get_option('blogname'))).'&lt;/a&gt;');

//Below Normaly < and > used
//$qixit_product_object->set_rmsg('Thanks+for+registration+on+<a+href="'.get_option('siteurl').'"+target="_blank">'.(str_replace(" ","+",get_option('blogname'))).'</a>');


   $qixit_product_object->set_siteurl(get_option('siteurl'));
   $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id=0&action=registration');
   $qixit_product_object->set_echo('qixit_id=(userid)');
   $url = $qixit_product_object->construct_product_url();
   //Admin Product for Registration
   if ( is_array( $url ) )
   {	
      $_SESSION['qixit_script_error_msg'] = $url['error_message'];
   }
   else
   {	
      $html=@file_get_contents($url);
      $matches = explode("|", $html);

      if ( is_array( $matches ) )
      {
         $PID=trim(substr(strip_tags(nl2br($matches[3])),16));
         if ( $PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1])))) == 'success') )
         {
            // add author registration qixit product
            $qixit_settings['qixit_admin_product_for_registration'] = $PID;
            update_option('qixit_settings',$qixit_settings);
         return true;
         }
         else //looks like QIXIT system returned an error
         {
            $result = trim(strip_tags(nl2br($matches[2])));
            if ( trim($result) != '' )
            {
               $_SESSION['qixit_script_error_msg'] = $result;
            }
            else
            {
               $_SESSION['qixit_script_error_msg'] = 'There was an error in connecting to the Qixit system.';
            }
         }
      }
      else
      {
         $_SESSION['qixit_script_error_msg'] = 'There was an error in connecting to the Qixit system.';
      }
   }
}

/**
 * add/update Qixit product for author post publish for "Guest Authors may publish an article for a price" option.
 */
function qixit_product_for_author_post_publish($cost, $blogname)
{   
   if ( $cost <= 0 )
   {
      return;
   }
   @session_start();
   $qixit_settings=get_option('qixit_settings');
   $qixit_product_object = new ProductAdd();
   if ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) &&  $qixit_settings['qixit_admin_product_for_author_post_publish'] != '')
   {
      //has PID, so its an update
      $qixit_product_object->set_qixit_pid($qixit_settings['qixit_admin_product_for_author_post_publish']);
   }
   $qixit_product_object->set_vend($qixit_settings['qixit_id']);
   $password=base64_decode($qixit_settings['qixit_password']);
   $qixit_product_object->set_vendpw($password);
   $qixit_product_object->set_desc('Author+Post+Publish+for+' . $blogname);
   $qixit_product_object->set_cost($cost);
   $qixit_product_object->set_aff($qixit_settings['qixit_id']);
   $qixit_product_object->set_affpct(QIXIT_AFFPCT);
   $qixit_product_object->set_purl(get_option('siteurl'));
   $qixit_product_object->set_rmsg('Thanks+for+publishing+the+post+on+' . get_option('siteurl'));
   $qixit_product_object->set_perm('N');
   $qixit_product_object->set_siteurl(get_option('siteurl'));
   $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id=0&action=post_publish');
   $qixit_product_object->set_echo('qixit_id=(userid)');
   $url=$qixit_product_object->construct_product_url();

   if ( is_array( $url ) )
   {
      $_SESSION['qixit_script_error_msg'] = $url['error_message'];
      return;
   }
   $html=@file_get_contents($url);
   $matches = explode("|", $html);
   if ( is_array( $matches ) )
   {
      $PID=trim(substr(strip_tags(nl2br($matches[3])),16));
      if ( $PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1])))) == 'success') )
      {
         $qixit_settings['qixit_admin_product_for_author_post_publish']=$PID;
         update_option('qixit_settings',$qixit_settings);
       return true;
      }
      else // looks like we got an error while retrieving data from QIXIT system
      {
         $result=trim(strip_tags(nl2br($matches[2])));
         if ( trim($result) != '' )
         {
            $_SESSION['qixit_script_error_msg'] = $result;
            return;
         }
         $_SESSION['qixit_script_error_msg'] = 'There was an error in connecting to the Qixit system.';
         return;
      }
   }
}

add_action('pre_update_option_blogname','qixit_pre_update_blogname');
function qixit_pre_update_blogname($blogname)
{
   
   $qixit_settings=get_option('qixit_settings');
   qixit_product_for_author_registration($qixit_settings['cost_to_be_author'], $blogname);
   qixit_product_for_author_post_publish($qixit_settings['cost_to_publish_post_by_author'], $blogname);
   return $blogname;
}

/**
 * When admin settings form is posted, validate and update the qixit settings for admin.
 *
 * called from wp hook ('admin_menu')
 */

function qixit_settings_for_admin()
{  
	global $wpdb;
   //QIXIT SETTING POST SECTION
   if ( isset($_POST) && (!empty($_POST)) )
   {    
      $light_box_started = false;
      $error_msg = array();
	  $options = array();
      $options = $_POST['qixit_settings'];

      // Validate account settings

      // qixit_id
      if ( trim($options['qixit_id']) == '' )
      {
         $error_msg[]="Qixit ID cannot be blank.";
      }
      else
      {
         $options['qixit_id']=trim($options['qixit_id']);
      }

      // qixit_password
      if ( trim($options['qixit_password']) == '' )
      {
         $error_msg[]="Qixit Password cannot be blank.";
      }
      else
      {
         $options['qixit_password']=trim($options['qixit_password']);
         $options['qixit_password'] = base64_encode($options['qixit_password']);
      }
		
		$invalid_password=false;
		if ( empty( $error_msg ) )
      { 	
			if ( $_POST['qixit_settings_previous_password']=='' || $_POST['qixit_settings_previous_password'] != $options['qixit_password'] )
			{  
				$success=qixit_product_for_author_registration(QIXIT_DEFAUL_COST, get_option('blogname'),$options['qixit_id'],$options['qixit_password']);
				if ( !($success) )
				{
					$invalid_password=true;
				}
			}
      }
      if ( empty( $error_msg ) && $invalid_password==false)
      {   
         // lets insert/update the qixit id and password, so that we can use it later
         $qixit_settings = get_option('qixit_settings');
         if($qixit_settings)
         {
            $qixit_settings['qixit_id'] = $options['qixit_id'];
            $qixit_settings['qixit_password'] = $options['qixit_password'];
            update_option('qixit_settings',$qixit_settings);
         }
         else
         {
            update_option('qixit_settings',$options);
         }
         $qixit_settings = get_option('qixit_settings');
         
         // cost
         if ( $options['cost'] == '' )
         {
            $options['cost'] = '0';
         }
         $number='';$number=explode('.',$options['cost']);
         if ( !is_numeric($options['cost']) || (float)$options['cost'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Enable premium content links, with a default price should have non-negative numeric value with only two digit after decimal.";
         }
   
   
         //paid_comments_price
         if ( $options['paid_comments'] == '1' )
         {
            update_option('default_comments_page','oldest'); 
            update_option('comment_order','asc'); 
         }
         
         //paid_comments_price
         if ( $options['paid_comments_price'] == '' )
         {
            $options['paid_comments_price']='0';
         }
         $number='';$number=explode('.',$options['paid_comments_price']);
         if ( !is_numeric($options['paid_comments_price']) || (float)($options['paid_comments_price']) < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Premium Comments default price should have non-negative numeric value with only two digit after decimal.";
         }
       
       //characters for admin
         if ( $options['characters'] == '' )
         {
            $options['characters']='500';
         }
         if ( !is_numeric($options['characters']) || (int)$options['characters'] <= 0 )
         {
            $error_msg[]="Characters should have non-negative integer value only.";
         }
         elseif ( is_numeric($options['characters']) && ereg("[.]",$options['characters']) ) 
         { 
           $error_msg[]="Characters should have non-negative integer value only.";
         }
         
         if ( is_numeric($options['characters']) && !ereg("[.]",$options['characters']) ) 
         { 
           $options['characters']=$options['characters'];
         }
         else
         {
           $options['characters']='500';
         }
      
         //===========================================================================================================================================
         //==================================== START REPEATED CODE Below code is also in qixit_admin_options_for_authors_settings() If any change here 
         //==================================== cost_to_be_author, cost_to_publish_post_by_author, post_cost_of_author, percent_to_author
         //===========================================================================================================================================
         //
         // cost_to_be_author		 
		 $options['percent_to_author'] = '';
         if ( $options['cost_to_be_author'] == '' )
         {
            $options['cost_to_be_author']='0';
         }
         $number='';$number=explode('.',$options['cost_to_be_author']);
         if ( !is_numeric($options['cost_to_be_author']) || (float)$options['cost_to_be_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Readers may setup Guest Author account for a price should have non-negative numeric value with only two digit after decimal.";
         }
         else
         {   
            if ( $options['cost_to_be_author'] > 0 )
            {      
               if ( empty($error_msg) )
               {
                  if (!(qixit_registration_help_page_exists()))
                  {
                        $data=array();
                        $data['post_title']='Author Registration Help';
                        $data['post_name']='author-registration-help';
   
                        $data['post_content']='This website is using Qixit\'s 2-Way Micropay&trade; plugin. <br/><br/> This service allows you to pay small amounts for premium placement of your comments . . . or to <strong>even become an author!</strong> <br/><br/> You can register as an author, for a small fee, and then post your own articles to this blog. <br/><br/> You pay only once to post an article.  You can edit your article for free.  You can also edit or delete comments posted about your article. <br/><br/> <br/> <strong>Earn Money With Your Articles</strong><br/><br/>You can insert as many links to affiliate products or your websites as you wish.<br/><br/><strong>Choosing Your ID Name</strong><br/><br/> When you <a href="'.get_option('siteurl').'/wp-login.php?action=register&type=author">create your authors\'s account</a>, please select an ID name which you want to be published with your articles. <br/><br/> <strong>PLEASE Note:</strong> We reserve the right to edit or delete your paid article placement if it violates our user agreement.  There are no refunds.';
                        $data['post_type']='page';   
                        $data['post_status']='publish';   
                        $data['comment_status']='closed';   
                        $data['ping_status']='closed';   
                        wp_insert_post($data);
                  }
                  if ( ( $_POST['qixit_settings_previous_cost_to_be_author'] != $options['cost_to_be_author'] ) || 
                           ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_registration',$qixit_settings) 
                                    && ($qixit_settings['qixit_admin_product_for_registration'] == '') ) ||
                           (  is_array($qixit_settings) && !array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
                     ) 
                  {    
                     $success=qixit_product_for_author_registration($options['cost_to_be_author'], get_option('blogname'));
                     if ( !($success) )
                     {
                       $options['cost_to_be_author']=$_POST['qixit_settings_previous_cost_to_be_author'];
                     }
                     else
                     {
                       $qixit_settings = get_option('qixit_settings');        
                       if ( array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
                       {
                         $options['qixit_admin_product_for_registration'] = $qixit_settings['qixit_admin_product_for_registration'];
                       }
                       if ( array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
                       {
                         $options['qixit_admin_product_for_author_post_publish'] = $qixit_settings['qixit_admin_product_for_author_post_publish'];
                       }
                         update_option('qixit_settings',$options); 
                     }
                  }
                }
               else
               {
                 $options['cost_to_be_author']=$_POST['qixit_settings_previous_cost_to_be_author'];
               }
            }
         }
      
         //cost_to_publish_post_by_author
         $number='';$number=explode('.',$options['cost_to_publish_post_by_author']);
         if ( !is_numeric($options['cost_to_publish_post_by_author']) || (float)$options['cost_to_publish_post_by_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Guest Authors may publish an article for a price should have non-negative numeric value only.";
         }
         else
         {
            $r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE name='".addslashes(QIXIT_AUTHOR_TAG)."'"));
            $slug=preg_replace( "/[^A-Za-z]/", "", QIXIT_AUTHOR_TAG);
            $r_set_slug = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE slug='".$slug."'"));
               
            if ( count($r_set) <= 0 && count($r_set_slug) <= 0 )
            {
               $inserted   =   $wpdb->insert( $wpdb->terms, array(
                                    'name' => QIXIT_AUTHOR_TAG,
                                    'slug' => $slug
               ));
               if ( $inserted )
               {
                  $term_id=mysql_insert_id();
               }
            }
            else
            {
               $term_id=$r_set[0]->term_id;
            }
      
            $r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->term_taxonomy." WHERE term_id ='".$term_id."'"));
            if ( count($r_set) <= 0 && $term_id )
            {
               $wpdb->insert( $wpdb->term_taxonomy, array(
                                    'term_id' => $term_id,
                                    'taxonomy'=>'post_tag',
                                    'description'=>'',
                                    'count' => '0'
                                    ));
            }
      
            if ( $options['cost_to_publish_post_by_author'] > 0 && $_POST['qixit_settings_previous_cost_to_publish_post_by_author']!=$options['cost_to_publish_post_by_author'] ||
                           ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) 
                                    && ($qixit_settings['qixit_admin_product_for_author_post_publish'] == '') ) ||
                           (  is_array($qixit_settings) && !array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
               )
            {      
           if ( empty($error_msg) )
           {          
                $success=qixit_product_for_author_post_publish($options['cost_to_publish_post_by_author'], get_option('blogname'));
             if ( !($success) )
             {
               $options['cost_to_publish_post_by_author']=$_POST['qixit_settings_previous_cost_to_publish_post_by_author'];
             }
            else
            {
              $qixit_settings = get_option('qixit_settings');        
              if ( array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
              {
               $options['qixit_admin_product_for_registration'] = $qixit_settings['qixit_admin_product_for_registration'];
              }
              if ( array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
              {
               $options['qixit_admin_product_for_author_post_publish'] = $qixit_settings['qixit_admin_product_for_author_post_publish'];
              }
              update_option('qixit_settings',$options); 
            }
           }
           else
           {
             $options['cost_to_publish_post_by_author']=$_POST['qixit_settings_previous_cost_to_publish_post_by_author'];
           }
            }
         }
         if ( $options['cost_to_publish_post_by_author'] == '' )
         {
            $options['cost_to_publish_post_by_author'] = '0';
         }

      
         // post_cost_of_author
         if ( $options['post_cost_of_author'] == '' )
         {
            $options['post_cost_of_author']='0';
         }
         $number='';$number=explode('.',$options['post_cost_of_author']);
         if ( !is_numeric($options['post_cost_of_author']) || (float)$options['post_cost_of_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Enable premium content settings for Authors, with a default price should have non-negative numeric value only.";
         }
      
         // percent_to_author
         if ( $options['percent_to_author'] == '' )
         {
            $options['percent_to_author']=QIXIT_DEFAUL_PERCENT_TO_AUTHOR;
         }
         if ( !is_numeric($options['percent_to_author']) || (float)$options['percent_to_author'] < 0 )
         {
            $error_msg[]="Author&rsquo;s percent share amount should have non-negative numeric value only.";
         }
         elseif ( is_numeric($options['percent_to_author']) && ( (float)$options['percent_to_author'] < 0 || (float)$options['percent_to_author'] > 100 ))
         {
            $error_msg[]="Author percent share should be between 0 and 100";
         }
         //
         //===========================================================================================================================================
         //==================================== END REPEATED CODE Below code is also in qixit_admin_options_for_authors_settings() If any change here 
         //==================================== cost_to_be_author, cost_to_publish_post_by_author, post_cost_of_author, percent_to_author
         //===========================================================================================================================================
      }
            
            
       //view_any_time_cookie_exp
       if ( !is_numeric($options['view_any_time_cookie_exp']) || (int)$options['view_any_time_cookie_exp'] < 0 )
       {
          $error_msg[]="Pay once view anytime cookie expiry time should have non-negative integer value only.";
       }
       elseif ( is_numeric($options['view_any_time_cookie_exp']) && ereg("[.]",$options['view_any_time_cookie_exp']) ) 
       { 
         $error_msg[]="Pay once view anytime cookie expiry time should have non-negative integer value only.";
       }
       if ( is_numeric($options['view_any_time_cookie_exp']) && !ereg("[.]",$options['view_any_time_cookie_exp']) ) 
       { 
         $options['view_any_time_cookie_exp']=60*60*24*$options['view_any_time_cookie_exp'];
       }
       else
       {
         $options['view_any_time_cookie_exp']=60*60*24*30;
       }
       if ( $options['view_any_time_cookie_exp'] == '' || $options['view_any_time_cookie_exp'] <= 0 )
       {
          $options['view_any_time_cookie_exp']=60*60*24*30;
       }
       
     
      if ( empty( $error_msg ) && $invalid_password==false )
      {
         $qixit_settings = get_option('qixit_settings');
         $all_settings = array();
         $all_settings['qixit_id'] = $options['qixit_id'];
         $all_settings['qixit_password'] = $options['qixit_password'];
         $all_settings['cost'] = $options['cost'];
         $all_settings['paid_comments_price'] = $options['paid_comments_price'];
         $all_settings['paid_comments'] = "0";
		 $all_settings['e_guest_authors'] = $options['e_guest_authors'];
		 $all_settings['paid_comments_only'] = $options['paid_comments_only'];
		 if ( array_key_exists('paid_comments', $options) )
         {
            $all_settings['paid_comments'] = $options['paid_comments'];
         }
         $all_settings['characters'] = $options['characters'];
         $all_settings['cost_to_be_author'] = $options['cost_to_be_author'];
         $all_settings['cost_to_publish_post_by_author'] = $options['cost_to_publish_post_by_author'];
         $all_settings['post_cost_of_author'] = $options['post_cost_of_author'];
         $all_settings['percent_to_author'] = $options['percent_to_author'];
         $all_settings['view_any_time_cookie_exp'] = $options['view_any_time_cookie_exp'];
         $all_settings['terms_of_service'] = $options['terms_of_service'];
         $all_settings['premium_bg_color'] = $options['premium_bg_color'];
         $all_settings['premium_heading_bg_color'] = $options['premium_heading_bg_color'];
         $all_settings['widget_name'] = $options['widget_name'];

   
         if ( array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
         {
            $all_settings['qixit_admin_product_for_registration'] = $qixit_settings['qixit_admin_product_for_registration'];
         }
         if ( array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
         {
            $all_settings['qixit_admin_product_for_author_post_publish'] = $qixit_settings['qixit_admin_product_for_author_post_publish'];
         }
         
         // now update all options
         update_option('qixit_settings',$all_settings);
		
		 $op_table = $wpdb->prefix."options";
		 $auth_regi = $options['e_guest_authors'];

		 	$sql = "update `$op_table` set `option_value` = $auth_regi where `option_name` = 'users_can_register' ";
		 	$rows_affected = $wpdb->query( $sql );
		 
		 
         $success='Settings saved.';
      }
   }
   else
   {
      $options = get_option('qixit_settings');
   }
   
   require_once QIXIT_PLUGIN_DIR . '/wp-qixit-admin-settings-form.php';
}

// qixit settings for author
function qixit_settings_for_author() 
{
   global $wpdb;

   $author_info = qixit_get_current_author_settings();
   if ( isset($_POST) && (!empty($_POST)) )
   {        
      $error_msg = array();
      $author_settings = $_POST['qixit_author_settings'];

      // Validate account settings
      if ( trim($author_settings['qixit_id']) == '' )
      {
         $error_msg[]="Qixit ID cannot be blank.";
      }
      else
      {
         $author_settings['qixit_id']=trim($author_settings['qixit_id']);
      }

      if ( empty( $error_msg ) )
      {  
         $current_user = wp_get_current_user();
         if ( !$author_info )
         {
            $wpdb->insert( $wpdb->prefix.QIXIT_AUTHOR_SETTINGS, array(
                     'wp_user_id' => $current_user->ID,
                     'qixit_id' => $author_settings['qixit_id'],
                     'date_created' => date('Y-m-d H:i:s')));
         }
         else
         {
            $wpdb->update( $wpdb->prefix.QIXIT_AUTHOR_SETTINGS,
            array('qixit_id' => $author_settings['qixit_id'],
                  'date_updated' => date('Y-m-d H:i:s')),
            array('wp_user_id' => $current_user->ID));
         }
   
         // get updated information
         $author_info = qixit_get_current_author_settings();
         $success='Settings saved.';
      }
   }
?>

<div class="wrap">
<h2><?php echo __('QIXIT Account Settings','qixit');?></h2>
   <?php 
      if ( !empty( $error_msg ) )
      {
         qixit_show_errors($error_msg);
      }
      
      if ( !empty( $success ) )
      {
         qixit_show_success($success);
      }    
   ?>

<!-- author settings form -->
<form method="post" action="">
<table class="form-table">
   <tr valign="top">
      <th scope="row" style="width: 30%"><?php echo __('Qixit ID','qixit');?></th>
      <td><input type="text" name="qixit_author_settings[qixit_id]" value="<?php echo $author_info->qixit_id; ?>" /></td>
   </tr>
</table>

<p class="submit">
   <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
<?php
}
?>
<?php
// add QIXIT Menu
add_action('admin_menu', 'qixit_menu');
function qixit_menu() 
{
   global $current_user;
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE)
   {
      add_menu_page(__('Qixit Cross Reference','qixit'), __('Qixit Pages','qixit'), 'publish_posts', 'qixit_cross_ref_page', 'qixit_cross_ref_page');
      add_submenu_page('qixit_cross_ref_page',__('Qixit Cross Reference','qixit'), __('Cross Reference','qixit'), 'publish_posts', 'qixit_cross_ref_page','qixit_cross_ref_page');
      add_submenu_page('qixit_cross_ref_page', __('Qixit Sales History','qixit'), __('Sales History','qixit'),'publish_posts', 'qixit_sales_history', 'qixit_sales_history');
      
      // only for administrator
      if ( $current_user->has_cap( "administrator" ) )
      {   
         add_options_page(__('QixIT Micropay','qixit'), __('QixIT Micropay','qixit'), 'manage_options', 'qixit_settings_for_admin', 'qixit_settings_for_admin');
         add_submenu_page('qixit_cross_ref_page',__('Ad hoc Link Create','qixit'), __('Ad hoc Link Add','qixit'), 'publish_posts', 'qixit_ad_hoc_create', 'qixit_ad_hoc_create');
         add_submenu_page('qixit_cross_ref_page',__('Ad hoc Link List','qixit'), __('Ad hoc Link List','qixit'), 'publish_posts', 'qixit_ad_hoc_list', 'qixit_ad_hoc_list');    
         add_submenu_page('qixit_cross_ref_page',__('QixIT Micropay','qixit'), __('QixIT Micropay','qixit'), 'publish_posts', 'qixit_settings_for_admin', 'qixit_settings_for_admin');    
      }
      else // for other roles
      {   
         add_users_page(__('QixIT Micropay','qixit'), __('QixIT Micropay','qixit'), 'qixit_settings_for_author', 'qixit_settings_for_author', 'qixit_settings_for_author');
      }      
      add_submenu_page('',__('Qixit Warning','qixit'), __('Qixit Warning','qixit'), 'publish_posts', 'qixit_post_product_delete_warning', 'qixit_post_product_delete_warning');
   }
}

/**
 * this is for only sub menu page. can can't be called
 */
add_action('admin_init', 'qixit_unwanted_sesssion_rid');
function qixit_unwanted_sesssion_rid()
{
  @session_start();
  if (isset($_SESSION['qixit_widget_error']))
  {
        unset($_SESSION['qixit_script_error_msg']);
  }
  if (isset($_SESSION['qixit_widget_success']))
  {
        unset($_SESSION['qixit_widget_success']);
  }
  unset($_SESSION['qixit_widget_error']);
  unset($_SESSION['qixit_widget_submitted_options']);
}

add_action('admin_init', 'qixit_post_product_deleted_redirect');
function qixit_post_product_deleted_redirect()
{
   @session_start();
   //to redirect contorl after deleting single post. wordpress redirect to http_refer page and http_reffer is qixit_post_product_delete_warning so to ignore qixit_warning page we redirect control to from where delete action was performed.
   if ( isset($_GET['deleted']) && $_GET['deleted'] ==' 1' && isset($_SESSION['delete_deny_url']) )
   {
      $delete_deny_url=$_SESSION['delete_deny_url'];
      unset($_SESSION['delete_deny_url']);
      wp_redirect($delete_deny_url);
      die(" ");
   }
}

add_action('admin_init', 'qixit_export_sales_report');
function qixit_export_sales_report()
{
   if ( isset($_GET['export']) && $_GET['export']=='csv' )
   {
      $where='';
      if ( isset($_GET['f']) && $_GET['f'] == 'ar' )
      {
         $where = " WHERE payment_for='author_registration' ";
      }
      if ( isset($_GET['f']) && $_GET['f'] == 'ap' )
      {
         $where = " WHERE payment_for='add_post' ";
      }
      if ( isset($_GET['f']) && $_GET['f'] == 'vadhoc' )
      {
         $where = " WHERE payment_for='view_ad_hoc' ";
      }
      if ( isset($_GET['f']) && $_GET['f'] == 'vp' )
      {
         $where = " WHERE payment_for='view_post' ";
      }
      if ( isset($_GET['f']) && $_GET['f'] == 'c' )
      {
         $where = " WHERE payment_for='comments' ";
      }
      $data_array = qixit_sales_history_data('fetch',$where);
      $headings[] = __('Paid For','qixit');
      $headings[] = __('Amount Paid','qixit');
      $headings[] = __('Date','qixit');
      $headings[] = __('Buyer&rsquo;s Qixit User ID','qixit');
      $headings[] = __('Qixit Transaction ID','qixit');
      $headings[] = __('Product Title','qixit');
      $headings[] = __('Amount Sharing','qixit');
      $headings[] = __('Author&rsquo;s percent share','qixit');
      $headings[] = __('Author','qixit');
      exportcsv_from_array($data_array,$headings);
   }
}


/**
 * called from admin_menu it is hidden page 
 */
function qixit_post_product_delete_warning()
{   
   @session_start();
   $delete_url=$_SESSION['delete_url'];
   $delete_deny_url=$_SESSION['delete_deny_url'];
   unset($_SESSION['delete_url']);
   //unset($_SESSION['delete_deny_url']); moved into qixit_admin_init
   ?>
   <p>
      <?php echo __("Are you sure you wish to delete this qixit product?",'qixit');?> 
      <br /><br />
      <?php echo __("If you will choose &rsquo;Yes&rsquo; then all the related payment detail and product detail will be deleted",'qixit');?>
   </p>      
   <form method="post" action="<?php echo $delete_url.'&confirm';?>" style="display:inline;">
      <input type="submit" name="submit" value="Yes" class="button" />
   </form>
   <form method="post" action="<?php echo $delete_deny_url;?>" style="display:inline;">
      <input type="submit" name="submit" value="No" class="button" />
   </form>
   <?php
}


//***************************************************************************************** Start Ad hoc Create 
/**
 * this is for only sub menu page. can can't be called
 */
function qixit_ad_hoc_create()
{  global $wpdb,$qixit_ad_hoc_product;
   if (isset($_POST) && (!empty($_POST)) )
   {   
      $error_msg = array();
      //post            
      $post_title          = $_POST['post_title'];
      $post_status         = $_POST['post_status'];
      //meta
      $qixit_post_meta                             = array();
      $qixit_post_meta['_qixit_cost']              = $_POST['_qixit_cost'];
      $qixit_post_meta['_qixit_pitch_url']         = $_POST['_qixit_pitch_url'];
      $qixit_post_meta['_qixit_delivery_url']      = $_POST['_qixit_delivery_url'];
      $qixit_post_meta['_qixit_ad_hoc_link_type']  = $_POST['_qixit_ad_hoc_link_type'];
      
      $post_meta = (object)$qixit_post_meta;
      
      // post_title   
      if ( $_POST['post_title'] == '' )
      {
         $error_msg[] = "Product Title cannot be blank.";
      }
      
      // _qixit_cost   
      $number='';$number=explode('.',$_POST['_qixit_cost']);
      if ( $_POST['_qixit_cost'] == '' || !is_numeric($_POST['_qixit_cost']) || (float)$_POST['_qixit_cost'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
      {
         $error_msg[]="Cost should have non-negative numeric value with only two digit after decimal.";
      }
      
      // Pitch Url   
      if ( $_POST['_qixit_pitch_url'] == '' || !qixit_is_valid_url($_POST['_qixit_pitch_url']) )
      {
         $error_msg[]="Pitch url should have correct url string.";
      }
      
      // Delivery Url
      if ( $_POST['_qixit_delivery_url'] == '' || !qixit_is_valid_url($_POST['_qixit_delivery_url']) )
      {
         $error_msg[]="Delivery url should have correct url string.";
      }
      
      
      if ( empty($error_msg) )
      {
         $returned_qixit_product_id='';
         //CODE TO WRITE POST 
         if ( !(isset($_POST['ID'])) )
         {
            $post_id = write_post();
            qixit_product_add_or_update($post_id);
         }
         else
         {   
             wp_update_post($_POST);
             $post_id = $_POST['ID'];
         }
         $_GET['edit_post_id'] = $post_id;
         
         update_post_meta( $post_id, '_qixit_cost', $post_meta->_qixit_cost);
         update_post_meta( $post_id, '_qixit_pitch_url', $post_meta->_qixit_pitch_url);
         update_post_meta( $post_id, '_qixit_delivery_url', $post_meta->_qixit_delivery_url );
         update_post_meta( $post_id, '_qixit_ad_hoc_link_type', $post_meta->_qixit_ad_hoc_link_type );
            
         $qixit_ad_hoc_product = new QixitAdHocProduct($post_id); 
         if ( !(isset($_POST['ID'])) )
         {   
            if (qixit_ad_hoc_product_add())
            {    
               $success = 'Ad hoc link created successfully.';
               $post['ID'] = $post_id;
               $post['post_status'] = 'publish';
               wp_update_post( $post );
            }
            else
            {
               $success = 'Ad hoc link created successfully but due to qixit error it has draft status.';
            }
         }
         elseif ( ($_POST['_qixit_cost'] != $_POST['pre_qixit_cost']) || ($_POST['post_title'] != $_POST['pre_post_title']))
         {   
            qixit_ad_hoc_product_update();
            $success = 'Ad hoc link updated.';
         }
         else
         {
            $success = 'Ad hoc link updated.';
         }
      }
     
   }
   else
   {
         $post_status         = 'draft';
   }
 
   if ( isset($_GET['edit_post_id']) )
   {  
      //post
      $post            = get_post( $_GET['edit_post_id'] );
      $post_title      = $post->post_title;
      $post_status     = $post->post_status;
      //meta
      $post_meta       = qixit_get_post_meta( $_GET['edit_post_id'] );
   }
 
?>
<div class="wrap">
   <h2><?php echo __('Ad hoc Link Creation','qixit');?></h2>
   <?php 
      if ( !empty( $error_msg ) )
      {
         qixit_show_errors($error_msg);
      }
      
      if ( !empty( $success ) )
      {
         qixit_show_success($success);
      } 
   ?>
   <br />
   <p>
   <?php echo __('This form allows you to quickly create a purchase link.  More options are available by signing into ','qixit');?>
   <a href="http://www.qixit.com/products/" target="_blank"><?php echo __('http://www.qixit.com/products/','qixit');?></a>
   <br />
   <?php echo __('where you can create or edit the product variables.','qixit');?>
   </p>
   <br />
   <form method="post" action="">
       <table class="form-table">
       
          <!-- Start Post row   -->
         <tr valign="top">
           <th scope="row"><?php echo __('Product Title','qixit');?></th>
           <td>
               <?php 
                  if ( isset($_GET['edit_post_id']) ) 
                  { 
                     ?>
                     <input type="hidden" name="ID" size="50" value="<?php echo $post->ID;?>" /> 
                     <?php
                   } 
               ?>
              <input type="text" name="post_title" size="50" value="<?php echo (!empty( $post_title ))?$post_title:'';?>" />
              <input type="hidden" name="pre_post_title" size="50" value="<?php echo (!empty( $post_title ))?$post_title:'';?>" />
              <input type="hidden" name="post_status" size="50" value="<?php echo $post_status;?>" />
              <input type="hidden" name="post_type" size="50" value="post" />
              <input type="hidden" name="visibility" size="50" value="public" />
              <input type="hidden" name="qixit_product_type" size="50" value="A" />
           </td>
         </tr>      
         <!--    End Post row      -->
   
          <!--    Start Post Meta fields -->
         <tr valign="top">
           <th scope="row"><?php echo __('Cost','qixit');?></th>
           <td><input type="text" name="_qixit_cost" size="5"  value="<?php echo (!empty($post_meta->_qixit_cost))?$post_meta->_qixit_cost:'' ;?>" />
               <input type="hidden" name="pre_qixit_cost" size="5"  value="<?php echo (!empty($post_meta->_qixit_cost))?$post_meta->_qixit_cost:'';?>" />
           example: 0.10  for ten cents
           </td>
         </tr>
         
         <tr valign="top">
           <th scope="row"><?php echo __('Ad hoc Link Type','qixit');?></th>
           <td>
           <select name='_qixit_ad_hoc_link_type' > 
           <option value='<?php echo QIXIT_PREMIUM_PAY_PER_VIEW;?>' 
                     <?php echo ( !empty($post_meta->_qixit_ad_hoc_link_type) && $post_meta->_qixit_ad_hoc_link_type == QIXIT_PREMIUM_PAY_PER_VIEW )?'selected':'';?> >
                     <?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_PER_VIEW);?>
            </option>
           <option value='<?php echo QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME;?>' 
                       <?php echo ( !empty($post_meta->_qixit_ad_hoc_link_type) && $post_meta->_qixit_ad_hoc_link_type == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )?'selected':'';?> >
                     <?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME);?>
           </option>
           </select>
           </td>
         </tr>

         
         
         <tr valign="top">
            <th scope="row"><?php echo __('Pitch Url','qixit');?></th>
            <td><input type="text" name="_qixit_pitch_url" size="50" value="<?php echo (!empty($post_meta->_qixit_pitch_url))?$post_meta->_qixit_pitch_url:'';?>" />
            <div style="width:500px;">
            The "Pitch URL" is the link from which you are offering the product/purchase link. It should begin with http:// 
            At minimum, it should be the home page of your blog.  Preferably, it should be the permalink
            of the page where you are publishing the purchase link. The pitch URL appears in the buyer's 
            receipt associated with the purchase and allows the buyer to return to the pitch/offer page.
            <div>
            </td>
            
         </tr>
         <tr valign="top">
            <th scope="row"><?php echo __('Delivery Url','qixit');?></th>
            <td><input type="text" name="_qixit_delivery_url" size="50" value="<?php echo (!empty($post_meta->_qixit_delivery_url))?$post_meta->_qixit_delivery_url:'';?>" />
            <div style="width:500px;">
            The "Delivery URL" is where the buyer should end up after payment has been confirmed.
            It should begin with http://  
            <br/>Note: The 2-Way Micropay engine also allows you to capture additional data on each buyer using optional data fields appended to the Pitch URL or report URL, such as
            age, gender, and zip code. This plugin currently collects only the buyer's Qixit ID, which can be used for paid follow-up offers through the Qixit email client. See <a href="http://2waymicropay.com/purchase.html" target="_blank">http://2waymicropay.com/purchase.html</a> for technical details.
            </div>
            </td>
         </tr>
          <!--    End Post Meta fields -->
         <tr valign="top">
            <th scope="row" colspan="2">
            <p class="submit"><input type="submit" name="ad_hoc_add" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
            </td>
         </tr>
         <?php
         if (!empty( $post ) || !empty( $success )) 
         {   
            ?>
            <tr valign="top">
               <th scope="row" colspan="2">
                  <strong><?php echo __('Purchase link: ','qixit');?></strong>
                  <?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?>
               </th>
            </tr>
            
            <tr valign="top">
               <th scope="row" colspan="2">
                  <strong><?php echo __('If, used within the blog, the following link will bring up a light box for confirmation of purchase','qixit');?>:</strong>
                  <br />
                  &lt;a class="adhoc-link" href="JavaScript:void(0)"
                  onclick="is_viewable('<?php echo preg_replace('/(https|http):\/\//','',$link);?>')" &gt;<?php echo $post_title;?>&lt;/a&gt;
               </th>
            </tr>
            
            <?php 
         }
         ?>
      </table>
   </form>
</div>
<?
}


/**
 * this is for only sub menu page. can can't be called
 */
function qixit_ad_hoc_list()
{
   global $wpdb,$current_user,$wp_locale,$qixit_ad_hoc_product;
   $qixit_no_record_found = true;   
   if ( isset($_GET['delete_post_id']) )
   { 
      if ( isset($_GET['confirm']) )
      {
         qixit_delete_qixit_product($_GET['delete_post_id']);
         wp_delete_post( $_GET['delete_post_id'], true);
      }
      else
      {
         ?>
         <p>
            <?php echo __("Are you sure you wish to delete this qixit ad hoc product?",'qixit');?> 
            <br /><br />
            <?php echo __("If you will choose &rsquo;Yes&rsquo; then all the related payment detail and product detail will be deleted",'qixit');?>
         </p>      
         <form method="post" action="<?php echo admin_url('admin.php?page=qixit_ad_hoc_list&delete_post_id='.$_GET['delete_post_id']);?>&confirm" style="display:inline;">
            <input type="submit" name="submit" value="Yes" class="button" />
         </form>
         <form method="post" action="<?php echo admin_url('admin.php?page=qixit_ad_hoc_list');?>" style="display:inline;">
            <input type="submit" name="submit" value="No" class="button" />
         </form>
         <?php
      }
   }
   
   if ( isset($_GET['status_change_post_id']) )
   {  

      $post=get_post( $_GET['status_change_post_id'] );
      if ( $post->post_status=='publish' )
      {
            $data['ID'] = $post->ID;
            $data['post_status'] = 'draft';
            wp_update_post($data);
      }
      else
      {
            $data['ID'] = $post->ID;
            $data['post_type'] = 'post';
            $data['post_status'] = 'publish';
            $qixit_ad_hoc_product = new QixitAdHocProduct($post->ID); 
            $qixit_settings = get_option('qixit_settings');            
            $_POST['post_type'] = 'post';
            if ( $qixit_ad_hoc_product->get_qixit_PID() == '' )
            { 
               if ( qixit_ad_hoc_product_add() )
               {   
                  $post_data['ID'] = $qixit_ad_hoc_product->get_post_id();
                  $post_data['post_status'] = 'publish';
                  wp_update_post( $post_data );
               }
            }
            else
            {
               $post_data['ID'] = $qixit_ad_hoc_product->get_post_id();
               $post_data['post_status'] = 'publish';
               wp_update_post( $post_data );
            }
      }
   }
   
   
   $qixit_products_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . " 
                                                                  WHERE qixit_PID  IS NOT NULL OR qixit_PID  IS NULL".qixit_date_filter_condition()." ") );
   
   if (!isset($_GET['delete_post_id']) || isset($_GET['confirm']))                                                               
   {
      if ( count( $qixit_products_post_ids ) > 0  ) 
      { 
         qixit_date_filter_drop_down(QIXIT_AD_HOC_PRODUCTS,'qixit_ad_hoc_list');
      ?>
      <table class="widefat"  >
         <thead>
            <tr>
               <th scope="col"  width="25%" ><?php echo __('Ad hoc link title','qixit');?></th>
               <th scope="col"  width="35%" ><?php echo __('Link','qixit');?></th>
               <th scope="col"  width="10%" ><?php echo __('Edit','qixit');?></th>
               <th scope="col"  width="10%"  ><?php echo __('Delete','qixit');?></th>
               <th scope="col"  width="10%" ><?php echo __('Publish/Unpublish','qixit');?></th>
               <th scope="col"  width="10%" ><?php echo __('Author','qixit');?></th>
            </tr>
         </thead>
         <tbody>
         <?php 
         foreach (  $qixit_products_post_ids  as $key => $qixit_products_post ) 
         {  
            $post       = get_post( $qixit_products_post->post_id );
            $post_meta  = qixit_get_post_meta( $qixit_products_post->post_id );
            $owner      = new WP_User( $post->post_author );
            if ( !$current_user->has_cap( "administrator" ) )
            {
               if ( $current_user->ID != $post->post_author )
               continue;
            }
            if ( $post_meta->_qixit_delivery_url != '' )
            {   $qixit_no_record_found = false;
               ?>
               <tr valign="top">
                  <td ><a href="<?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?>"><?php echo $post->post_title;?></a></td>
                  <td ><?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?></td>
                  <td ><a href="admin.php?page=qixit_ad_hoc_create&edit_post_id=<?php echo $post->ID;?>"><?php echo __('Edit','qixit');?></a></td>
                  <td ><a href="admin.php?page=qixit_ad_hoc_list&delete_post_id=<?php echo $post->ID;?>" ><?php echo __('Delete','qixit');?></a></td>
                  <td >
                        <a href="admin.php?page=qixit_ad_hoc_list&status_change_post_id=<?php echo $post->ID;?>" 
                           title="<?php echo __("Change to ",'qixit').(($post->post_status=='publish')?__('Draft','qixit'):__('Publish'));?>">
                           <?php echo __(ucwords($post->post_status),'qixit');?>
                        </a>
                  </td>
                  <td ><?php echo $owner->display_name; ?></td>
               </tr>
               <?php
            }
         } //endforeach
         ?>
         </tbody>
      </table>
      <?php 
      }
      else
      {
         $qixit_no_record_found = true;
      }
      
      if ($qixit_no_record_found)
      {
         ?>
         <table width="100%" >
               <tr>
                  <td ><?php echo __('No data found.','qixit');?></td>
               </tr>
         </table>
         <?php
      }
   }
}

/**
 * this is for only qixit_ad_hoc_list() and qixit_cross_ref_page()
 */
function qixit_date_filter_condition()
{
   //start Query
   if ( ( isset($_GET['m']) && $_GET['m']=='0' ) || !isset($_GET['m']) )
   {
      $date_filter='';
   }
   else
   {
      $date_filter=" and YEAR(date_created)='".substr($_GET['m'],0,4)."' and MONTH(date_created)='".substr($_GET['m'],4,6)."'";
   }
   //End Query
   return $date_filter;
}

/**
 * this is for only qixit_ad_hoc_list() and qixit_cross_ref_page()
 */
function qixit_date_filter_drop_down($table,$page)
{
   global $wpdb,$wp_locale;
   //start filter box
   $arc_result = $wpdb->get_results( $wpdb->prepare(" SELECT DISTINCT YEAR(date_created) AS yyear, MONTH(date_created) AS mmonth FROM ".$wpdb->prefix.$table." 
                                                      ORDER BY date_created DESC") );
   $month_count = count($arc_result);
   if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) 
   {
      $m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
      ?>
      <div style="padding-top:9px;padding-bottom:9px">
      <form method="get" >
         <input type="hidden" name="page" value="<?php echo $page; ?>" />
         <select name='m'>
         <option <?php selected( $m, 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
         <?php
         foreach ($arc_result as $arc_row) 
         {
            if ( $arc_row->yyear == 0 )
            {
               continue;
            }
            
            $arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );
            
            if ( $arc_row->yyear . $arc_row->mmonth == $m ) 
            {
               $default = ' selected="selected"';
            }
            else
            {
               $default = '';
            }
                                          
            echo "<option $default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
            echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
            echo "</option>\n";
         }
         ?>
         </select>
         <input type="submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
      </form>
      </div>
      <?php 
   } 
   //end filter box
}

//***************************************************************************************** End Ad hoc Create 

//***************************************************************************************** Start Sales History
// Hook into the 'wp_dashboard_setup' action to register our other functions
// dashboard functions
add_action('wp_dashboard_setup', 'qixit_add_dashboard_box' );
function qixit_add_dashboard_box()
{
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE)
   {
      global $current_user;
      $qixit_settings = get_option('qixit_settings');
      
      $qixit_show_sales_history_dashboard_box = true;   
   
      if ( !$current_user->has_cap( "administrator" ) && $qixit_settings['cost_to_publish_post_by_author'] <= 0 )
      {
         $qixit_show_sales_history_dashboard_box = false;
      }
      
      if ( $qixit_show_sales_history_dashboard_box )
      {
         wp_add_dashboard_widget('qixit_sales_history_dashboard_box', 'Qixit Sales History', 'qixit_sales_history_dashboard_box');
      }
   }
}
function qixit_sales_history_dashboard_box() 
{
   global $wpdb,$current_user;
   $qixit_no_record_found = true;
   ?>
   <div class="inside">
      <div class="table" >
      <?php      
      $sales_history_set = $wpdb->get_results( $wpdb->prepare(" SELECT * FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . " as pd 
                                                                LEFT JOIN " . $wpdb->prefix.QIXIT_PRODUCTS . " as qp ON pd.product_id=qp.product_id 
                                                                order by date_purchased desc limit 0,5") );
      if ( count($sales_history_set) > 0 ) 
      {  
         ?>
         <table width="100%" >
            <tr>
               <td width="5%"><strong><?php echo __('ID','qixit');?></strong></td>
               <td width="45%"><strong><?php echo __('Post Title','qixit');?></strong></td>
               <td width="35%"><strong><?php echo __('Payment for','qixit');?></strong></td>
               <td width="15%"><strong><?php echo __('Author','qixit');?></strong></td>
            </tr>
            <?php 
            $i=1;
            
            foreach ( $sales_history_set  as $key => $sales_history ) 
            {  
               if ( $sales_history->type == 'A' && $sales_history->payment_for == 'view_ad_hoc')
               {   
                  $sales_ad_hoc_history = $wpdb->get_row( $wpdb->prepare(" SELECT * FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS . " as pd 
                                                                            LEFT JOIN " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . " as a ON pd.product_id=a.product_id 
                                                                            WHERE a.qixit_PID='".$sales_history->qixit_PID."'") );
               }

               if ( $sales_history->payment_for == 'view_ad_hoc' ) 
               {  $post  = get_post($sales_ad_hoc_history->post_id);
                  $owner = new WP_User( $post->post_author );
                  if ( !$current_user->has_cap( "administrator" ) )
                  {
                     if ( $current_user->ID != $post->post_author )
                     continue;
                  }
                  $qixit_no_record_found = false;
                  ?>
                  <tr>
                     <td ><?php echo $i;?></td>
                     <td >
                        <?php echo __('Ad hoc Prodcut','qixit').": ";?>
										<a href='<?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?>'><?php echo $post->post_title;?></a>
                     </td>
                     <td ><?php echo __('View Ad Hoc Product','qixit');?></td>
                     <td ><?php echo $owner->display_name;?></td>
                  </tr>
                  <?php
               }

               if ( $sales_history->payment_for == 'view_post' || $sales_history->payment_for == 'comments' ) 
               {  $post  = ( $sales_history->type == 'P' )?get_post($sales_history->post_id):get_post($sales_ad_hoc_history->post_id);
                  $owner = new WP_User( $post->post_author );
                  if ( !$current_user->has_cap( "administrator" ) )
                  {
                     if ( $current_user->ID != $post->post_author )
                     continue;
                  }
                  $qixit_no_record_found = false;
                  ?>
                  <tr>
                     <td ><?php echo $i;?></td>
                     <td >
                        <?php echo ( $sales_history->type == 'A' )?__('Ad hoc Prodcut','qixit').": ":'';?>
                        <?php if ( $sales_history->type == 'A' ) 
                              {
                                 ?>
                                 <a href='<?php $link = get_permalink($post->ID); echo (stristr($link,'?'))?$link.'&adhoc':$link.'?adhoc';?>'><?php echo $post->post_title;?></a>
                                 <?php
                              }
                              else
                              {
                                 ?>
                                 <a href='<?php echo get_permalink($post->ID);?>'><?php echo $post->post_title;?></a>
                                 <?php 
                              } 
                              ?>
                     </td>
                     <td ><?php echo ( $sales_history->payment_for=='view_post' )?__('View post','qixit'):__('Comment','qixit');?></td>
                     <td ><?php echo $owner->display_name;?></td>
                  </tr>
                  <?php
               }
               
               if ( $current_user->has_cap( "administrator" ) && ($sales_history->payment_for == 'author_registration' || $sales_history->payment_for == 'add_post') )
               {  if ($sales_history->payment_for == 'add_post')
                  {  
                     $post  = get_post($sales_history->post_id); 
                     $owner = new WP_User( $post->post_author );
                  }
                  if ($sales_history->payment_for == 'author_registration')
                  {
                     $owner = new WP_User( $sales_history->wp_user_id );
                  }
                  $qixit_no_record_found = false;
                  ?>
                  <tr>
                     <td ><?php echo $i;?></td>
                     <td >
                        <?php
                           if ($sales_history->payment_for == 'add_post')
                           {
                              ?>
                              <a href='<?php echo get_permalink($post->ID);?>'><?php echo $post->post_title;?></a>
                              <?php
                           }
                           if ($sales_history->payment_for == 'author_registration')
                           {
                              echo __('New Registration','qixit');
                           }
                         ?>
                     </td>
                     <td ><?php echo ( $sales_history->payment_for=='add_post' )?__('Post publish','qixit'):__('Author registration','qixit');?></td>
                     <td ><?php if (isset($owner->display_name)) echo $owner->display_name;?></td>
                  </tr>
                  <?php
               }
               $i++;
            } //endforeach
            ?>
         </table>
         <?php 
      }   
      else
      {
         $qixit_no_record_found = true;
      }
      
      if ($qixit_no_record_found)
      {
         ?>
         <table width="100%" >
               <tr>
                  <td ><?php echo __('No data found.','qixit');?></td>
               </tr>
         </table>
         <?php
      }
      ?>      
      </div>
      <div class="qixit_total_sale_on_dashbaord" >
         <?
            if ( $current_user->has_cap( "administrator" ) )
            {
               $last_month_start_date = date('Y-m-d H:i:s',mktime(0, 0, 0, date("m")-1, 1, date("Y")));
               $last_month_end_date = date('Y-m-d H:i:s',mktime(0, 0, -1, date("m"), 1, date("Y")));
               $where = " WHERE date_purchased BETWEEN '$last_month_start_date' and '$last_month_end_date' ";
               $sales_data = qixit_sales_history_data(false, $where);
               if (!empty($sales_data))
               {
                  echo '<strong>'.__('Total Sales (last month)','qixit').': '. $sales_data['total_amount'] . '</strong>';
                  //
                  echo '<br>';
                  //
                  $start_date_of_the_year = date('Y-m-d H:i:s',mktime(0, 0, 0, 1, 1, date("Y")));
                  $today = date('Y-m-d H:i:s');
                  $where = " WHERE date_purchased BETWEEN '$start_date_of_the_year' and '$today' ";
                  $sales_data = qixit_sales_history_data(false,$where);
                  echo '<strong>'.__('Total Sales (year to date)','qixit').': '.$sales_data['total_amount'].'</strong>';
               }
            }
            else
            {
               $last_month_start_date = date('Y-m-d H:i:s',mktime(0, 0, 0, date("m")-1, 1, date("Y")));
               $last_month_end_date = date('Y-m-d H:i:s',mktime(0, 0, -1, date("m"), 1, date("Y")));
               $where = " WHERE date_purchased BETWEEN '$last_month_start_date' and '$last_month_end_date' ";
               $sales_data = qixit_sales_history_data(false,$where);
               if (!empty($sales_data))
               {
                  echo '<strong>'.__('Total Sales (last month)','qixit').': '.$sales_data['total_amount_for_author'].'</strong>';
                  //
                  echo '<br>';
                  //
                  $start_date_of_the_year = date('Y-m-d H:i:s',mktime(0, 0, 0, 1, 1, date("Y")));
                  $today = date('Y-m-d H:i:s');
                  $where = " WHERE date_purchased BETWEEN '$start_date_of_the_year' and '$today' ";
                  $sales_data = qixit_sales_history_data(false,$where);
                  echo '<strong>'.__('Total Sales (year to date)','qixit').': '.$sales_data['total_amount_for_author'].'</strong>';
               }
            }
            
         ?>
      </div>
   </div>
   <?php
}
/**
 * called form Sales History submenu
 */
function qixit_sales_history()
{
   $where='';
   if ( isset($_GET['f']) && $_GET['f'] == 'ar' )
   {
      $where = " WHERE payment_for='author_registration' ";
   }
   if ( isset($_GET['f']) && $_GET['f'] == 'ap' )
   {
      $where = " WHERE payment_for='add_post' ";
   }
	if ( isset($_GET['f']) && $_GET['f'] == 'vadhoc' )
   {
      $where = " WHERE payment_for='view_ad_hoc' ";
   }
   if ( isset($_GET['f']) && $_GET['f'] == 'vp' )
   {
      $where = " WHERE payment_for='view_post' ";
   }
   if ( isset($_GET['f']) && $_GET['f'] == 'c' )
   {
      $where = " WHERE payment_for='comments' ";
   }
   qixit_sales_history_data('echo',$where);
}
//***************************************************************************************** End Sales History

//***************************************************************************************** Start Post title update

add_action('admin_footer','qixit_show_script_error_msg');
function qixit_show_script_error_msg()
{   
   @session_start(); 
   if ( isset($_SESSION['qixit_script_error_msg']) && $_SESSION['qixit_script_error_msg'] != '' )
   {
      if ( trim('Provided Vendor Information supplied is invalid') == trim($_SESSION['qixit_script_error_msg']) ) 
      {
         echo '<div class="error"><p>';
         echo __('Provided Vendor ID or Password invalid. You must upgrade your Qixit ID to a vendor account at','qixit');
         echo " <a href='".QIXIT_LEARN_MORE_LINK."' target='_blank' >www.Qixit.com/products</a>.";
         echo '</p></div>';            
      }
      else
      {
         echo '<div class="error"><p>';
         echo __($_SESSION['qixit_script_error_msg'],'qixit');
         echo '</p></div>';      
      }
      unset($_SESSION['qixit_script_error_msg']);
   }
}


function qixit_post_product_add()
{
   
   global $wpdb, $qixit_product;
   $qixit_settings = get_option('qixit_settings');
   $post = get_post( $qixit_product->get_post_id() );
   if (is_null($post))
   {   
      return;
   }
   $post_id = $post->ID;   
   if ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME || $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
   {
      if ( $_POST['qixit_post_cost'] <= 0 )
      {
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg'] = "Post Cost should be greater than zero OR the content should be 'Regular'.";
         return;
      }   
      $post_owner = new WP_User( $post->post_author );
      $qixit_product_object = new ProductAdd();
      $qixit_product_object->set_vend( $qixit_settings['qixit_id'] );
      $password = base64_decode( $qixit_settings['qixit_password'] );
      $qixit_product_object->set_vendpw( $password );
      
      if ( $post_owner->has_cap( "administrator" ) )
      {
         $qixit_product_object->set_aff( $qixit_settings['qixit_id'] );
         $qixit_product_object->set_affpct( QIXIT_AFFPCT );
      }
      else
      {
         $author_info = qixit_get_author_settings( $post_owner->ID );
         $qixit_product_object->set_aff( $author_info->qixit_id );
         $qixit_product_object->set_affpct( $qixit_settings['percent_to_author'] );
      }

      $desc = trim($_POST['post_title'])!=''?$_POST['post_title']:$post->ID;
      $qixit_product_object->set_desc( $desc );
      $qixit_product_object->set_cost( $_POST['qixit_post_cost'] );
      $qixit_product_object->set_purl( get_option('siteurl') );
       
      if  ($_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )
      {
         $qixit_product_object->set_perm('Y');
      }
      elseif ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
      {
         $qixit_product_object->set_perm('N');
      }
      $qixit_product_object->set_siteurl(get_option('siteurl'));
      $qixit_product_object->set_permalink(get_permalink($post->ID));
      $qixit_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.Here\'s+the+link+if+you+want+to+see+' . $qixit_product_object->get_desc() .'+again..');
      
      $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='. $post->ID);
      $qixit_product_object->set_echo('qixit_id=(userid)');
      $url=$qixit_product_object->construct_product_url();
      
      if ( is_array($url) )
      {  
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg'] = $url['error_message'];
         return;
      }
      
      $html=@file_get_contents($url);
      $matches = explode("|", $html);          
      if ( is_array($matches) )
      {   
         $PID = trim(substr(strip_tags(nl2br($matches[3])),16));
         if ( $PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1])))) == 'success') )
         {   
            $data =  array( 'post_qixit_PID' => $PID,
                            'qixit_post_type' => $_POST['qixit_post_type'],
                            'premium_post_cost' => $_POST['qixit_post_cost'] );
            $qixit_product = qixit_update_qixit_product( $qixit_product->get_post_id(), $data);
            // everything looks good, so lets return
            return;
         }
         //looks like we encountered an error
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg'] = qixit_get_qixit_system_error($matches); 
         return;
      }
      else
      {   
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
      }
      
   }
   
}

function qixit_comment_product_add()
{
   global $wpdb, $qixit_product;
   $qixit_settings = get_option( 'qixit_settings' );

   // add a comments QIXIT product only if paid_comments is set
   if ( $qixit_settings['paid_comments'] == 1 && (isset($_POST['qixit_comment_cost']) && $_POST['qixit_comment_cost'] > 0) )
   {
      $post = get_post( $qixit_product->get_post_id() );
      if (is_null($post))
      {
         return;
      }
      $post_id = $post->ID;
      $qixit_comment_product_object = new ProductAdd();

      $qixit_comment_product_object->set_vend( $qixit_settings['qixit_id'] );
      $password = base64_decode( $qixit_settings['qixit_password'] );
      $qixit_comment_product_object->set_vendpw( $password );
      $post_owner = new WP_User( $post->post_author );
      
      if ( !$post_owner->has_cap( "administrator" ))
      {
         $author_info = qixit_get_author_settings( $post_owner->ID );
         $qixit_comment_product_object->set_aff( $author_info->qixit_id );
         $qixit_comment_product_object->set_affpct( $qixit_settings['percent_to_author'] );
      }
      else
      {
         $qixit_comment_product_object->set_aff( $qixit_settings['qixit_id'] );
         $qixit_comment_product_object->set_affpct( QIXIT_AFFPCT );
      }
      
      $desc = trim($_POST['post_title'])!=''?$_POST['post_title']:$post_id;
      $qixit_comment_product_object->set_desc('Comments+on+'.$desc);
      $qixit_comment_product_object->set_cost($_POST['qixit_comment_cost']);
      $qixit_comment_product_object->set_purl(get_option('siteurl'));
      $qixit_comment_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.++Your+comment+was+posted+at+' . get_permalink($_POST['post_ID']) . '.'); 
      $qixit_comment_product_object->set_perm('Y');
      $qixit_comment_product_object->set_siteurl(get_option('siteurl'));
      $qixit_comment_product_object->set_permalink(get_permalink($post_id));
      $qixit_comment_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='.$post_id.'&action=premium_comment');
      $qixit_comment_product_object->set_echo('qixit_id=(userid)');
      $url = $qixit_comment_product_object->construct_product_url();

      if ( is_array($url) )
      {
         $_SESSION['qixit_script_error_msg'] = $url['error_message'];
         return;
      }

      $html=@file_get_contents($url);
      $matches = explode("|", $html);

      if ( is_array($matches) )
      {
         $PID = trim(substr(strip_tags(nl2br($matches[3])),16));
         if ( $PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1]))))=='success') )
         {
            $data =  array( 'comments_qixit_PID' => $PID,
                      'premium_comments_cost' => $_POST['qixit_comment_cost']);
            
             $qixit_product = qixit_update_qixit_product( $qixit_product->get_post_id(), $data);  
            // everything looks good, so lets return   
            return;  
         }
         // looks like we encountered an error
         $_SESSION['qixit_script_error_msg'] = qixit_get_qixit_system_error($matches);
         return;
      }
      else
      {
         $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
      }
   }
}


function qixit_post_product_update()
{ 
   global $wpdb, $qixit_product;
   @session_start();
   $qixit_settings = get_option('qixit_settings');
   $post = get_post($qixit_product->get_post_id());
   $post_id = $post->ID;
   
   if ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME || $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
   {
      if ( $_POST['qixit_post_cost'] <= 0 )
      {
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg'] = "Post Cost should be greater than zero OR the content should be 'Regular'.";
         return;
      }
      $post_owner = new WP_User( $post->post_author );
      $qixit_product_object=new ProductAdd();
      $qixit_product_object->set_vend($qixit_settings['qixit_id']);
      $password = base64_decode($qixit_settings['qixit_password']);
      $qixit_product_object->set_vendpw($password);
      
      if ( $post_owner->has_cap( "administrator" ) )
      {   
         $qixit_product_object->set_aff($qixit_settings['qixit_id']);
         $qixit_product_object->set_affpct(QIXIT_AFFPCT);
      }
      else
      {
         $author_info = qixit_get_author_settings($post_owner->ID);
         $qixit_product_object->set_aff($author_info->qixit_id);
         $qixit_product_object->set_affpct($qixit_settings['percent_to_author']);
      }
         
      $desc = trim($_POST['post_title'])!=''?$_POST['post_title']: $qixit_product->get_post_id();
      $qixit_product_object->set_desc($desc);
      $qixit_product_object->set_cost($_POST['qixit_post_cost']);
      $qixit_product_object->set_purl(get_option('siteurl'));
      $qixit_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.Here\'s+the+link+if+you+want+to+see+' . $qixit_product_object->get_desc() .'+again..');
      if ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )
      {
         $qixit_product_object->set_perm('Y');
      }
      elseif ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
      {
         $qixit_product_object->set_perm('N');
      }
      $qixit_product_object->set_siteurl(get_option('siteurl'));
      $qixit_product_object->set_permalink(get_permalink($qixit_product->get_post_id()));
      $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='. $qixit_product->get_post_id());
      $qixit_product_object->set_echo('qixit_id=(userid)');
      $qixit_product_object->set_qixit_pid($qixit_product->get_post_qixit_PID());
      $url = $qixit_product_object->construct_product_url(); 
      if ( is_array($url) )
      {
         qixit_set_old_status_post($post->ID);
         $_SESSION['qixit_script_error_msg']=$url['error_message'];
         return;
      }
      $html=@file_get_contents($url);
   
      $matches = explode("|", $html);
      if ( is_array($matches) )
      {
         $result=trim(strip_tags(nl2br($matches[2])));
         if ( stristr($result,'Updated') )
         {
            // everything looks good
            return true;
         }
         else
         {
            if ( trim($result)!='' )
            {
               qixit_set_old_status_post($post->ID);
               $_SESSION['qixit_script_error_msg']=$result;
               return;
            }
         
            qixit_set_old_status_post($post->ID);
            $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
            return;
         }
      }
   }
}

function qixit_comment_product_update()
{
   @session_start();
   global $wpdb, $qixit_product;
   $post = get_post( $qixit_product->get_post_id() );
   $post_owner = new WP_User( $post->post_author );
   $post_id = $post->ID;
   $qixit_settings = get_option('qixit_settings');
   
   if ( $qixit_settings['paid_comments'] == 1 )
   {
      if ( $_POST['qixit_comment_cost'] <= 0 )
      {
         $_SESSION['qixit_script_error_msg'] = "Comment Cost should be greater than zero";
         return;
      }    
               
      $qixit_comment_product_object = new ProductAdd();
      $qixit_comment_product_object->set_vend($qixit_settings['qixit_id']);
      $password = base64_decode($qixit_settings['qixit_password']);
      $qixit_comment_product_object->set_vendpw($password);
      $desc=trim($_POST['post_title'])!=''?$_POST['post_title']: $qixit_product->get_post_id();
      $qixit_comment_product_object->set_desc('Comments+on+'.$desc);
      $qixit_comment_product_object->set_cost($_POST['qixit_comment_cost']);
      
      if ( !$post_owner->has_cap( "administrator" ))
      {
         $author_info = qixit_get_author_settings( $post_owner->ID );
         $qixit_comment_product_object->set_aff( $author_info->qixit_id );
         $qixit_comment_product_object->set_affpct( $qixit_settings['percent_to_author'] );     
      }
      else
      {
         $qixit_comment_product_object->set_aff($qixit_settings['qixit_id']);
         $qixit_comment_product_object->set_affpct(QIXIT_AFFPCT);
      }
      
      $site_url=get_option('siteurl');
      $qixit_comment_product_object->set_purl($site_url);
      $qixit_comment_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.++Your+comment+was+posted+at+' . get_permalink( $qixit_product->get_post_id() ) . '.');
      $qixit_comment_product_object->set_perm('Y');
      $qixit_comment_product_object->set_siteurl(get_option('siteurl'));
      $qixit_comment_product_object->set_permalink(get_permalink( $qixit_product->get_post_id() ));
      $qixit_comment_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='.$qixit_product->get_post_id().'&action=premium_comment');
      $qixit_comment_product_object->set_echo('qixit_id=(userid)');
      $qixit_comment_product_object->set_qixit_pid($qixit_product->get_comments_qixit_PID());
      $url = $qixit_comment_product_object->construct_product_url();
      if ( is_array($url) )
      {
         $_SESSION['qixit_script_error_msg']=$url['error_message'];
         return;
      }
      $html=@file_get_contents($url);
   
      $matches = explode("|", $html);
      if ( is_array($matches) )
      {
         $result=trim(strip_tags(nl2br($matches[2])));
         if ( stristr($result,'Updated') )
         {
            // everything looks good
            return;
         }
         else
         {
            if ( trim($result) != '' )
            {
               $_SESSION['qixit_script_error_msg'] = $result;
               return;
            }
            $_SESSION['qixit_script_error_msg'] = 'There was an error in connecting to the Qixit system.';
            return;
         }
      }
   }
}

function qixit_ad_hoc_product_add()
{
   @session_start();
   global $wpdb, $qixit_ad_hoc_product;
   if ( !isset($_POST['qixit_product_type']) )
   {
      $_POST['qixit_product_type'] = 'A';
   }
   if ( isset($_POST['_qixit_cost']) && $_POST['_qixit_cost'] <= 0 )
   {
      $_SESSION['qixit_script_error_msg'] = "Ad hoc Cost should be greater than zero.";
      return;
   }

   $qixit_settings = get_option('qixit_settings');      
   $post = get_post( $qixit_ad_hoc_product->get_post_id() );
   if ($post != null)
   {
      $post_id = $post->ID;
      $post_owner = new WP_User( $post->post_author );
   
      $qixit_product_object = new ProductAdd();
      $qixit_product_object->set_vend( $qixit_settings['qixit_id'] );
      $password = base64_decode( $qixit_settings['qixit_password'] );
      $qixit_product_object->set_vendpw( $password );
      
      if ( $post_owner->has_cap( "administrator" ) )
      {
         $qixit_product_object->set_aff( $qixit_settings['qixit_id'] );
         $qixit_product_object->set_affpct( QIXIT_AFFPCT );
      }
      
      if (!isset($_POST['post_title']))
      {
        $desc = trim($post->post_title)!=''?$post->post_title:$post->ID;
      }
      else
      {
        $desc = trim($_POST['post_title'])!=''?$_POST['post_title']:$post_id;
      }
      
      $qixit_product_object->set_desc( $desc );
      
      if (!isset($_POST['_qixit_cost']))
      {
        $qixit_cost = $qixit_ad_hoc_product->get_cost();
      }
      else
      {
        $qixit_cost = $_POST['_qixit_cost'];
      }
      $qixit_product_object->set_cost( $qixit_cost );
      $qixit_product_object->set_purl( get_option('siteurl') ); 
            
      if  ($_POST['_qixit_ad_hoc_link_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )
      {
         $qixit_product_object->set_perm('Y');
      }
      elseif ( $_POST['_qixit_ad_hoc_link_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
      {
         $qixit_product_object->set_perm('N');
      }
      
      $qixit_product_object->set_siteurl(get_option('siteurl'));
      $qixit_product_object->set_permalink(get_permalink($post_id));
      $qixit_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.Here\'s+the+link+if+you+want+to+see+' . $qixit_product_object->get_desc() .'+again..');
      $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='. $post_id);
      $qixit_product_object->set_echo('qixit_id=(userid)');
      $url=$qixit_product_object->construct_product_url();
      if ( is_array($url) )
      {  
         qixit_set_old_status_post($post_id);
         $_SESSION['qixit_script_error_msg'] = $url['error_message'];
         return;
      }
      
      $html=@file_get_contents($url);
      $matches = explode("|", $html);
      if ( is_array($matches) )
      {
         $PID = trim(substr(strip_tags(nl2br($matches[3])),16));
         if ( $PID != '' && (strtolower(trim(strip_tags(nl2br($matches[1])))) == 'success') )
         {
            $data =  array( 'qixit_PID' => $PID,
                            'cost' => $qixit_cost );
            $qixit_ad_hoc_product = qixit_update_qixit_product( $qixit_ad_hoc_product->get_post_id(), $data);
            // everything looks good, so lets return
            return true;
         }
         $_SESSION['qixit_script_error_msg'] = qixit_get_qixit_system_error($matches);
         return;
      }
      else
      {   
         $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
      }
   }
}

function qixit_ad_hoc_product_update()
{ 
   global $wpdb, $qixit_ad_hoc_product;
   @session_start();
   $qixit_settings = get_option('qixit_settings');
   
   if ( !isset($_POST['qixit_product_type']) )
   {
      $_POST['qixit_product_type'] = 'A';
   }
   
   if ( $_POST['_qixit_cost'] <= 0 )
   {
      $_SESSION['qixit_script_error_msg'] = "Ad hoc Cost should be greater than zero.";
      return;
   }    
   
   if ($qixit_ad_hoc_product->get_qixit_PID()=='')
   {
      if (qixit_ad_hoc_product_add())
      {
         $post['ID'] = $qixit_ad_hoc_product->get_post_id();
         $post['post_status'] = 'publish';
         wp_update_post( $post );
         return ;
      }
   }
   
   $post = get_post( $qixit_ad_hoc_product->get_post_id() );
   $post_id = $post->ID;
   $post_owner = new WP_User( $post->post_author );
   
   $qixit_product_object=new ProductAdd();
   $qixit_product_object->set_vend($qixit_settings['qixit_id']);
   $password = base64_decode($qixit_settings['qixit_password']);
   $qixit_product_object->set_vendpw($password);
   
   if ( $post_owner->has_cap( "administrator" ) )
   {   
      $qixit_product_object->set_aff($qixit_settings['qixit_id']);
      $qixit_product_object->set_affpct(QIXIT_AFFPCT);
   }
   else
   {
      /*
      $author_info = qixit_get_author_settings($post_owner->ID);
      $qixit_product_object->set_aff($author_info->qixit_id);
      $qixit_product_object->set_affpct($qixit_settings['percent_to_author']);
      */
   }
   $desc = trim($_POST['post_title'])!=''?$_POST['post_title']: $qixit_ad_hoc_product->get_post_id();
   $qixit_product_object->set_desc($desc);
   $qixit_product_object->set_cost($_POST['_qixit_cost']);
   $qixit_product_object->set_purl(get_option('siteurl'));
   $qixit_product_object->set_rmsg('Thanks+for+reading+' . get_option('siteurl') . '.Here\'s+the+link+if+you+want+to+see+' . $qixit_product_object->get_desc() .'+again..');      
   if  ($_POST['_qixit_ad_hoc_link_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )
   {
      $qixit_product_object->set_perm('Y');
   }
   elseif ( $_POST['_qixit_ad_hoc_link_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )
   {
      $qixit_product_object->set_perm('N');
   }
   $qixit_product_object->set_siteurl(get_option('siteurl'));
   $qixit_product_object->set_permalink(get_permalink($qixit_ad_hoc_product->get_post_id()));
   $qixit_product_object->set_durl(QIXIT_PLUGIN_URL.'/wp-qixit-redirect.php?post_id='. $qixit_ad_hoc_product->get_post_id());
   $qixit_product_object->set_echo('qixit_id=(userid)');
   $qixit_product_object->set_qixit_pid($qixit_ad_hoc_product->get_qixit_PID());
   $url = $qixit_product_object->construct_product_url(); 
   if ( is_array($url) )
   {
      qixit_set_old_status_post($post_id);
      $_SESSION['qixit_script_error_msg']=$url['error_message'];
      return;
   }
   $html=@file_get_contents($url);

   $matches = explode("|", $html);
   if ( is_array($matches) )
   {
      $result=trim(strip_tags(nl2br($matches[2])));
      if ( stristr($result,'Updated') )
      {
         // everything looks good
         $data = array( 'cost' => $_POST['_qixit_cost'] );
         $qixit_ad_hoc_product = qixit_update_qixit_product( $qixit_ad_hoc_product->get_post_id(), $data );
         return true;
      }
      else
      {
         if ( trim($result)!='' )
         {
            qixit_set_old_status_post($post_id);
            $_SESSION['qixit_script_error_msg']=$result;
            return;
         }
      
         qixit_set_old_status_post($post_id);
         $_SESSION['qixit_script_error_msg']='There was an error in connecting to the Qixit system.';
         return;
      }
   }
}

/**
 * Set OLD_STATUS in session
 */
add_action('transition_post_status','qixit_transition_post_status','',3);
function qixit_transition_post_status($new_status, $old_status, $post)
{
   global $qixit_post_old_status;
   
   if ( $old_status != 'inherit' && $old_status != 'new' && $new_status != $old_status )
   {
      $qixit_post_old_status = $old_status;
   }
}

function qixit_product_add_or_update( $post_id )
{

   global $wpdb, $current_user, $qixit_product;
  
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
   $qixit_product = new QixitProduct($qixit_post_id);
   $qixit_ad_ho_product = new QixitAdHocProduct($qixit_post_id);
   $qixit_post_cost = (isset($_POST['qixit_post_cost'])) ? $_POST['qixit_post_cost'] : $qixit_settings['cost'];
   $qixit_comment_cost =  (isset($_POST['qixit_comment_cost'])) ? $_POST['qixit_comment_cost'] : $qixit_settings['paid_comments_price'];

   // looks like we do not have a qixit product yet in our db, so lets insert one
   if ( $qixit_product->get_product_id() == '' && $qixit_ad_ho_product->get_product_id() == '' )
   {   
      if (isset($_POST['qixit_product_type']) && trim($_POST['qixit_product_type'])=='A')
      {
         $data = array(
                  'post_id' => $qixit_post_id,
                  'cost' => $_POST['_qixit_cost']
                     );
         $qixit_ad_hoc_product = qixit_add_qixit_product($post, $data);
      }
      else
      {      
            $data = array(
               'post_id' => $qixit_post_id,
               'qixit_post_type' => $_POST['qixit_post_type'],
               'premium_post_cost' => $qixit_post_cost,
               'premium_comments_cost' => $qixit_comment_cost
            );
            $qixit_product = qixit_add_qixit_product($post, $data);
      }                  
      
   }
   else // update
   {
      if (isset($_POST['qixit_product_type']) && trim($_POST['qixit_product_type'])=='A')
      {
         //this data will be update in qixit_ad_hoc_product_update after updation on qixit.com
      }
      else
      { 
         $has_post_product_changed = false;
         $has_comments_product_changed = false;
         $old_post = get_post( $qixit_post_id );      
         $data = array(
                  'qixit_post_type' => $_POST['qixit_post_type'],
                  'premium_post_cost' => $qixit_post_cost,
                  'premium_comments_cost' => $qixit_comment_cost);
                           
         if ( $qixit_product->get_premium_comments_cost() != $qixit_comment_cost ||
              $old_post->post_title != $_POST['post_title'] )
         { 
            $has_comments_product_changed = true;         
         }
         
         if ( $qixit_product->get_qixit_post_type() != $_POST['qixit_post_type'] ||
              $qixit_product->get_premium_post_cost() != $qixit_post_cost || 
              $old_post->post_title != $_POST['post_title'] )
         {
            $has_post_product_changed = true;         
         }       
         $qixit_product->set_has_post_product_changed( $has_post_product_changed );
         $qixit_product->set_has_comments_product_changed( $has_comments_product_changed );
         qixit_update_qixit_product( $qixit_product->get_post_id(), $data );
      }
   }
   
}


add_action('save_post','qixit_save_post');
function qixit_save_post($post_id)
{  
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE && isset($_POST['post_type']) && $_POST['post_type'] == 'post')
   {   
      global $current_user;
      $post=get_post($post_id);
      $qixit_settings = get_option('qixit_settings');
      //if ($post->post_status != 'auto-draft')
      if ($post->post_status != 'auto-draft' && isset($_POST['action']) && $_POST['action'] != 'autosave' )
      { 
         if ( isset($_POST['action']) && ( $_POST['action'] == 'post-quickpress-save' || $_POST['action'] == 'post-quickpress-publish' ) ) //quickpress = Quickpress
         {
            $_POST['qixit_post_type'] = QIXIT_REG;
            if ( !$current_user->has_cap( "administrator" ) )
            {
               $_POST['qixit_post_cost'] = $qixit_settings['post_cost_of_author'];  
            }
            else
            {
               $_POST['qixit_post_cost'] = $qixit_settings['cost'];
            }
            $_POST['qixit_comment_cost'] = $qixit_settings['paid_comments_price'];
         }
         
         if ( isset($_POST['action']) && $_POST['action'] == 'inline-save' ) //inline-save = quick edit
         {   
            $qixit_product = new QixitProduct($_POST['ID']);
            $_POST['qixit_post_type'] = $qixit_product->get_qixit_post_type();
            $_POST['qixit_post_cost'] = $qixit_product->get_premium_post_cost();
            $_POST['qixit_comment_cost'] = $qixit_product->get_premium_comments_cost();
         }  
          
         if ( (!isset($_GET['action'])) || (isset($_GET['action']) && $_GET['action'] != 'restore') )
         {   
           qixit_product_add_or_update($post_id);   
         } 
      }
   }
}


add_action('publish_post','qixit_publish_post');
function qixit_publish_post($post_id)
{ 
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE && (isset($_REQUEST['post_type']) && $_REQUEST['post_type']=='post') )
   {
      @session_start();
      global $wpdb, $current_user, $qixit_product;
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
      if ( !$qixit_product ) 
      { 
         $qixit_product = new QixitProduct($qixit_post_id);
      }
      $qixit_ad_hoc_product_durl = get_post_meta( $qixit_post_id, '_qixit_delivery_url' ); 
      if ( !empty($qixit_ad_hoc_product_durl) )
      {
         return;
      }
      // author post publish
      if ( !$current_user->has_cap( "administrator" ) && !$current_user->has_cap( "editor" ) && (float)$qixit_settings['cost_to_publish_post_by_author'] > 0 )
      {         
         if ((float)$qixit_settings['percent_to_author'] < 0)
         {    
            if ( isset($_POST['action']) && ( $_POST['action'] == 'post-quickpress-save' || $_POST['action'] == 'post-quickpress-publish' ) ) //quickpress = Quickpress
            {
               $_SESSION['qixit_script_error_msg'] = "Author percent is not set. Contact the administrator.";
               qixit_set_save_draft_post($post_id);
               qixit_show_script_error_msg();
               $_POST['action'] = 'post-quickpress-save';
            }
            else
            {
               qixit_set_old_status_post($post_id);
               $_SESSION['qixit_script_error_msg'] = "Author percent is not set. Contact the administrator.";
               return;
            }
         }
         else
         {   
            $qixit_post_cost = ($_POST['qixit_post_cost']) ? $_POST['qixit_post_cost'] : $qixit_settings['post_cost_of_author'];
            if ( $qixit_settings['paid_comments'] == 1)
            {            
               $qixit_comment_cost = ($_POST['qixit_comment_cost']) ? $_POST['qixit_comment_cost'] : $qixit_settings['paid_comments_price'];
            }
            $qixit_PID_for_author_post_publish = $qixit_settings['qixit_admin_product_for_author_post_publish'];
      
            $qixit_payment_details = $wpdb->get_row($wpdb->prepare("SELECT *  FROM " . $wpdb->prefix.QIXIT_PAYMENT_DETAILS .  " 
                                                      WHERE product_id='".$qixit_product->get_product_id()."' and qixit_PID = '$qixit_PID_for_author_post_publish'"));
            // we know that this is the first time the post is being published
            if ( !$qixit_payment_details )
            {   
               $data = array( 'post_id' => $qixit_post_id,
                        'qixit_post_type' => $_POST['qixit_post_type'],
                        'premium_post_cost' => $qixit_post_cost,
                        'premium_comments_cost' => $qixit_comment_cost);
                        
               $qixit_product = new QixitProduct($qixit_post_id); 
               if ( !$qixit_product ) 
               {   
                  $qixit_product = qixit_add_qixit_product($post, $data);  
               }
               else
               {
                   $qixit_product = qixit_update_qixit_product( $qixit_post_id, $data);
               }     
               $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['post_title'] = $_POST['post_title'];
               $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['post_ID'] = $_POST['post_ID'];       
               $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['qixit_post_type'] = $_POST['qixit_post_type'];       
               qixit_set_old_status_post($post_id);         
               // comments product
               if ( !is_null($qixit_product) && $qixit_product->get_comments_qixit_PID() == '')
               {
                  // add comment product to QIXIT system
                  qixit_comment_product_add();
               }
               else if ( !is_null($qixit_product) && $qixit_product->get_has_comments_product_changed() )
               {
                  qixit_comment_product_update();
               }
               
               if ( isset($_POST['action']) && ( $_POST['action'] == 'post-quickpress-save' || $_POST['action'] == 'post-quickpress-publish' ) ) //quickpress = Quickpress
               {
                  ?>
                  <script>
                    window.parent.location='<?php echo admin_url('post.php?action=edit&post='.$_POST['post_ID']);?>';
                  </script>
                  <?php
                  die(" "); //don't remove this otherwise control will not redirect.
               }
               else
               {
                  wp_redirect(admin_url('post.php?action=edit&post='.$_POST['post_ID']));
                  die(" "); //don't remove this otherwise control will not redirect.            
               }
            }
            else
            {   
               if ($_POST['action'] != 'post-quickpress-publish')
               {   
                  // post product add
                  if ( $qixit_product->get_post_qixit_PID() == '' )
                  {
                     //add post product to QIXIT system
                     qixit_post_product_add();
                  }
                  // comments product add
                  if ( $qixit_product->get_comments_qixit_PID() == '')
                  {
                     // add comment product to QIXIT system
                     qixit_comment_product_add();
                  }
               }
            }
         }
      }
      else
      {   
         // post product add
         if ( $qixit_product->get_post_qixit_PID() == '' )
         {   
            //add post product to QIXIT system
            qixit_post_product_add();
         }
         // comments product add
         if ( $qixit_product->get_comments_qixit_PID() == '')
         {
            // add comment product to QIXIT system
            qixit_comment_product_add();
         }
      }
   
      // post product update
      if ( $qixit_product->get_has_post_product_changed() )
      {   
         qixit_post_product_update();
      }
      // comments product update
      if ( $qixit_product->get_has_comments_product_changed() ) 
      {
         qixit_comment_product_update();
      }
      
      if ( isset($_POST['action']) && ( $_POST['action'] == 'post-quickpress-save' || $_POST['action'] == 'post-quickpress-publish' ) ) //quickpress = Quickpress
      {   
         qixit_show_script_error_msg();
      }
   }
}

if ( qixit_admin_settings_found() === true )
{
   add_action('admin_menu', 'qixit_add_box');
}
function qixit_add_box()
{
   if ( current_user_can( 'publish_posts' ) && QIXIT_USEABLE)
   {
      add_meta_box(
      'qixit_box', // id of the <div> we'll add 
      __('Qixit','qixit'), //title
      'qixit_add_content_in_the_box', // callback function that will echo the box content 
      'post', // where to add the box: on "post", "page", or "link" page 
      'side',
      'high'
      );
   }
}

function qixit_add_content_in_the_box()
{
   
   if ( isset($_GET['post']) && $_GET['post'] != '' )
   {
      $self_url=admin_url('post.php'.'?post='.$_GET['post'].'&action=edit');   
   }
   else
   {
      $self_url=admin_url('post-new.php');
   }

   
   global $current_user;
   $qixit_settings = get_option('qixit_settings');
   
   if (isset($_GET['post']))
   {
      $qixit_product = new QixitProduct($_GET['post']);
   }
   else
   {
      $qixit_product = new QixitProduct();
   }
   
?>
   <script>
      //<![CDATA[
         function qixit_content_type_cancel_button()
         {   
            jQuery(document).ready(function($) 
            { 
               $('#qixit_post_type_edit').toggle('blind');$('#qixit_post_type_edit_label').show();   
            });
         }
         
         function qixit_content_type_ok_button()
         {   
            jQuery(document).ready(function($) 
            {
                $('#qixit_post_type_display').html(qixit_post_type_title($('#qixit_post_type').val()));
                $('#qixit_post_type_edit').toggle('blind');
                $('#qixit_post_type_edit_label').show();
                $('#qixit_post_type_edit_label_id').show();
                if ($('#qixit_post_type').val() != '<?php echo QIXIT_REG;?>')
                {
                   $('#post_cost_entry').show();
                }
                else
                {
                   $('#post_cost_entry').hide();
                }             
            });
         }
         
         function qixit_post_cost_cancel_button()
         {   
            jQuery(document).ready(function($) 
            { 
               $('#qixit_post_cost').val($('#init_qixit_post_cost').val()); 
               $('#qixit_post_cost_edit').toggle('blind');
               $('#qixit_post_cost_edit_label').show();   
            });
         }
         
         function qixit_post_cost_ok_button()
         {   
            jQuery(document).ready(function($) 
            {
               if ( (isNaN($('#qixit_post_cost').val())) || ($('#qixit_post_cost').val() <= 0))
               {
                  alert('<?php echo __('Please enter integer value only.','qixit');?>');
                  $('#qixit_post_cost').val($('#init_qixit_post_cost').val());
               }
               else
               {   valueStr=($('#qixit_post_cost').val()).split('.');
                  if (valueStr[1]) 
                  {   
                     if ( (valueStr[1]).length>2 )
                     {
                        alert('<?php echo __('You can use 2 digit only after decimal.','qixit');?>');
                        $('#qixit_post_cost').val($('#init_qixit_post_cost').val());
                     }
                  }
                  $('#init_qixit_post_cost').val($('#qixit_post_cost').val());
                  $('#qixit_post_cost_display').html($('#qixit_post_cost').val());
                  $('#qixit_post_cost_edit').toggle('blind');
                  $('#qixit_post_cost_edit_label').show();
               }
            });
         }
   
         function qixit_comment_cost_cancel_button()
         {  
            jQuery(document).ready(function($) 
            { 
               $('#qixit_comment_cost_edit').toggle('blind');
               $('#qixit_comment_cost').val($('#init_qixit_comment_cost').val()); 
               $('#qixit_comment_cost_edit_label').show();  
            });
         }
         
         function qixit_comment_cost_ok_button()
         {   
            jQuery(document).ready(function($) 
            {
               if ( (isNaN($('#qixit_comment_cost').val())) || ($('#qixit_comment_cost').val() <= 0) )
               {
                  alert('<?php echo __('Please enter integer value only.','qixit');?>');
                  $('#qixit_comment_cost').val($('#init_qixit_comment_cost').val());
               }
               else
               {
                  valueStr=($('#qixit_comment_cost').val()).split('.');
                  if (valueStr[1]) 
                  {   
                     if ( (valueStr[1]).length>2 )
                     {
                        alert('<?php echo __('You can use 2 digit only after decimal.','qixit');?>');
                        $('#qixit_comment_cost').val($('#init_qixit_comment_cost').val());
                     }
                  }
                  $('#init_qixit_comment_cost').val($('#qixit_comment_cost').val());
                  $('#qixit_comment_cost_display').html($('#qixit_comment_cost').val());
                  $('#qixit_comment_cost_edit').toggle('blind');$('#qixit_comment_cost_edit_label').show();
                 }
            });
         }
   
   //]]>
   </script>
   <div class="qixit-section" ><br />
   <!--  Post Type -->
   <div id="content_type">
   <?php echo __('Content','qixit');?>: <span> 
      <strong>
      <span id="qixit_post_type_display"><?php echo ( isset($_POST['qixit_post_type'] ))? $_POST['qixit_post_type']: qixit_post_type_title( $qixit_product->get_qixit_post_type() );?>
      </span>&nbsp;
      </strong>
   </span> 
   <span id="qixit_post_type_edit_label_id"> 
   <!--
   admin_url(post.php?46&action=edit#post_status)
   -->

   <a href="<?php echo $self_url.'#qixit';?>" id='qixit_post_type_edit_label' onClick="jQuery('#qixit_post_type_edit').toggle('blind');this.style.display='none'"><?php echo __('Edit','qixit');?></a>
   </span>
   <br />
      <div id='qixit_post_type_edit'><br />
      <select name='qixit_post_type' id='qixit_post_type'> 
         <option value='<?php echo QIXIT_REG;?>'
         <?php echo (( isset( $_POST['qixit_post_type'] ) && ( $_POST['qixit_post_type'] == QIXIT_REG )) || ( $qixit_product->get_qixit_post_type() == QIXIT_REG ))?'selected':'';?>><?php echo qixit_post_type_title(QIXIT_REG);?></option>
         <option value='<?php echo QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME;?>'
         <?php echo (( isset( $_POST['qixit_post_type'] ) && ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME )) || ( $qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME ))?'selected':'';?>><?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME);?></option>
         <option value='<?php echo QIXIT_PREMIUM_PAY_PER_VIEW;?>'
         <?php echo (( isset( $_POST['qixit_post_type'] ) && ( $_POST['qixit_post_type'] == QIXIT_PREMIUM_PAY_PER_VIEW )) || ( $qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW ))?'selected':'';?>><?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_PER_VIEW);?></option>
      </select> <br />
      <br />
      
      <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_content_type_ok_button()" class="button"><?php echo __('OK','qixit');?></a> 
       <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_content_type_cancel_button()"><?php echo __('Cancel','qixit');?></a>
      </div>
   
   </div>
   <!--  End Post Type -->
   
   <!--  Post Cost --> 
   <div id="post_cost_entry" style="display:<?php echo ( $qixit_product->get_qixit_post_type() != QIXIT_REG)?'block':'none';?>" >
      <br />
      <div id="post_cost">
         <span style="float: left"><?php echo __('Post Cost','qixit');?>:
            <strong> 
            <span id="qixit_post_cost_display">
            <?php 
               if ( $qixit_product->get_premium_post_cost() != '' )
               {
                  echo round($qixit_product->get_premium_post_cost(),2); 
               }
               else
               {
                  if ( $current_user->has_cap( "administrator" ) )
                  {
                     echo round($qixit_settings['cost'],2);
                  }
                  else
                  {
                     echo round($qixit_settings['post_cost_of_author'],2);
                  }
               }
            ?>
            </span>&nbsp;
            </strong> 
         </span> &nbsp; 
         <span id="qixit_post_cost_edit_label_id" class='qixit_show'> 
            <a href="<?php echo $self_url.'#qixit';?>" id='qixit_post_cost_edit_label' onClick="jQuery('#qixit_post_cost_edit').toggle('blind');this.style.display='none'">
               <?php echo __('Edit','qixit');?>
            </a>
         </span> 
         <input type="hidden" name="init_qixit_post_cost" id="init_qixit_post_cost"   value="<?php echo round(($qixit_product->get_premium_post_cost() != '')?$qixit_product->get_premium_post_cost():(($current_user->has_cap( "administrator" ))?$qixit_settings['cost']:$qixit_settings['post_cost_of_author']),2);?>" />
         
         <div id='qixit_post_cost_edit'><br />
            <input type="text" name="qixit_post_cost" id="qixit_post_cost"   value="<?php echo round(($qixit_product->get_premium_post_cost() != '')?$qixit_product->get_premium_post_cost():(($current_user->has_cap( "administrator" ))?$qixit_settings['cost']:$qixit_settings['post_cost_of_author']),2);?>" />
            <br />
            <br />
            <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_post_cost_ok_button()" class="button"><?php echo __('OK','qixit');?></a> 
            <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_post_cost_cancel_button()"><?php echo __('Cancel','qixit');?></a>
         </div>
      </div>
   </div>
   <!--  End Post Cost --> 
   
   <!-- Comment Cost -->
      <?php
         $post_owner = ''; 
         if ( isset( $_GET['post'] ))
         {
            $post = get_post($_GET['post']);
         }
         ?>
         <br />
         <div id="comment_cost">
           <span style="float: left"><?php echo __('Comment Cost','qixit');?>:
             <strong>
             <span id="qixit_comment_cost_display"> 
             <?php if ($qixit_product->get_premium_comments_cost() != '' )
             {
               echo round($qixit_product->get_premium_comments_cost(),2);
             }
             else
             {
               echo round($qixit_settings['paid_comments_price'],2);
             }
             ?> 
             </span>&nbsp;
             </strong> 
           </span> &nbsp; 
           <?php $class="class='qixit_show'"; ?>
           <span id="qixit_comment_cost_edit_label_id" <?php echo $class;?>> 
             <a   href="<?php echo $self_url.'#qixit';?>" id='qixit_comment_cost_edit_label' onClick="jQuery('#qixit_comment_cost_edit').toggle('blind');this.style.display='none'">
               <?php echo __('Edit','qixit');?>
             </a>
           </span> 
         <input type="hidden" name="init_qixit_comment_cost" id="init_qixit_comment_cost"
         value="<?php echo round(($qixit_product->get_premium_comments_cost() > 0)?$qixit_product->get_premium_comments_cost():$qixit_settings['paid_comments_price'],2);?>" />
            <div id='qixit_comment_cost_edit'><br />
         <input type="text" name="qixit_comment_cost" id="qixit_comment_cost"   
         value="<?php echo round(($qixit_product->get_premium_comments_cost() > 0)?$qixit_product->get_premium_comments_cost():$qixit_settings['paid_comments_price'],2);?>" />
             <br />
             <br />
             <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_comment_cost_ok_button()" class="button"><?php echo __('OK','qixit');?></a>
            <a href="<?php echo $self_url.'#qixit';?>" onClick="qixit_comment_cost_cancel_button()"><?php echo __('Cancel','qixit');?></a>
           </div>
         </div>    
   <!-- End Comment Cost -->  
   </div>
   <?php 
}

?>
<?php
/**
 * when post delete function is called.
 */
add_action( 'delete_post', 'qixit_post_delete' );
function qixit_post_delete($post_id)
{
   qixit_delete_qixit_product($post_id);
   return $post_id;
}
/**
 * when post delete function is called.
 */
add_action( 'check_admin_referer', 'qixit_post_product_delete' );
function qixit_post_product_delete()
{
   @session_start();
   if (isset($_GET['action']) && isset($_GET['post']) && $_GET['action']=='delete')
   {   
      if (is_array($_GET['post']))
      {
         $post=get_post($_GET['post'][0]);
      }
      else
      {
         $post=get_post($_GET['post']);
      }
      
      if ( isset($post) && (!isset($_GET['confirm'])) && $post->post_type == 'post')
      {
         if (is_array($_GET['post']))
         {
            $_SESSION['delete_url']=admin_url('edit.php'.'?'.$_SERVER['QUERY_STRING']);
         }
         else
         {
            $_SESSION['delete_url']=admin_url('post.php'.'?'.$_SERVER['QUERY_STRING']);
         }
         
         if ( isset($_GET['_wp_http_referer']) )
         {
            $paged=explode('&',stristr($_GET['_wp_http_referer'],'&paged='));
            if (count($paged)>1) 
            {
               $paged_arr=explode('=',$paged[1]);
               $page_no=$paged_arr[1];
            }
            else
            {
               $page_no='';            
            }
         }
         else
         {
            $page_no='';
         }
         $_SESSION['delete_deny_url']=admin_url('edit.php?post_status=trash&post_type=post'.(( $page_no != '' )?'&paged='.$page_no:''));
         wp_redirect(admin_url('admin.php?page=qixit_post_product_delete_warning'));
         die(" ");
      }
   }
}

/**
 * Bulk Edit Post qixit action permform
 */
add_action( 'check_admin_referer', 'qixit_bulk_post_change_status_after_post' );
function qixit_bulk_post_change_status_after_post()
{ 
   @session_start();
   global $wpdb, $qixit_product;         
   $qixit_settings = get_option('qixit_settings');
   if  ( isset($_REQUEST['post']) && isset($_GET['qixit_bulk_post_status_option']) && $_GET['qixit_bulk_post_status_option'] != '-1'
            && stristr($_SERVER['PHP_SELF'],'/edit.php') )
   {   
      if ( count($_REQUEST['post']) > QIXIT_MAX_BULK_ACTION )
      {
         $_SESSION['qixit_script_error_msg'] = "You can't have more then ".QIXIT_MAX_BULK_ACTION." post to apply qixit action.";
         return ;
      }

      foreach( $_REQUEST['post'] as $key => $post_id )
      {   
         $post = get_post($post_id);
         $user = new WP_User( $post->post_author );
         $cost = ($user->has_cap( "administrator" ))?$qixit_settings['cost']:$qixit_settings['post_cost_of_author'];
         $qixit_product = new QixitProduct($post_id); 
         if ( !$qixit_product )
         {   
            $data = array( 'post_id' => $post_id,
                           'qixit_post_type' => $_POST['qixit_bulk_post_status_option'],
                           'premium_post_cost' => $cost,
                           'premium_comments_cost' => $qixit_settings['paid_comments_price']
                         );
            $qixit_product = qixit_add_qixit_product($post, $data);
         }
         else
         {            
            $_POST['post_title']      = $post->post_title;
            $_POST['qixit_post_cost'] = $qixit_product->get_premium_post_cost();
            $_POST['qixit_post_type'] = $_GET['qixit_bulk_post_status_option']; 
            if ( $qixit_product->get_post_qixit_PID() != '' )
            {    if ( $_POST['qixit_post_type'] == QIXIT_REG )
                 {   
                     $data =  array( 'qixit_post_type' => $_POST['qixit_post_type']);                                   
                     $qixit_product = qixit_update_qixit_product( $qixit_product->get_post_id(), $data);
                 }
                 if ( qixit_post_product_update($qixit_product, $post_id) )
                 {
                     $data =  array( 'qixit_post_type' => $_POST['qixit_post_type'],
                                     'premium_post_cost' => $_POST['qixit_post_cost'] 
                                   );
                                   
                     $qixit_product = qixit_update_qixit_product( $qixit_product->get_post_id(), $data);
                 }              
            }
            else
            {   
               qixit_post_product_add();
            }
          }
       } //end foreach( $_REQUEST['post'] as $key => $post_id )
   }
}

add_action( 'manage_posts_columns', 'qixit_add_post_type_column' );
/**
 * qixit content type column is added on edit.php
 */
function qixit_add_post_type_column($columns)
{
   $newcolumns1=array_slice($columns,0,2);
   $newcolumns1['qixit_post_type']=__('Qixit Post Type','qixit');
   $newcolumns2=array_slice($columns,2);
   $columns=array_merge($newcolumns1,$newcolumns2);
   return $columns;
}

add_action( 'manage_posts_custom_column', 'qixit_post_type_column' );
/**
 * used to show content type column value in cell
 */
function qixit_post_type_column($column_name)
{
   global $wpdb,$post;
   if ($column_name == 'qixit_post_type')
   {
      $qixit_product = new QixitProduct($post->ID); 
      if ( !$qixit_product || $qixit_product->get_product_id() == '' )
      {    
         $qixit_settings = get_option('qixit_settings');
         
         $user = new WP_User( $post->post_author );
         if ( $user->has_cap( "administrator" ))
         {
            $cost = $qixit_settings['cost'];
         }
         else
         {
            $cost = $qixit_settings['post_cost_of_author'];
         } 
         $data = array( 'post_id' => $post->ID,
                        'qixit_post_type' => QIXIT_REG,
                        'premium_post_cost' => $cost,
                        'premium_comments_cost' => $qixit_settings['paid_comments_price']
                      );
         $qixit_product = qixit_add_qixit_product($post, $data); 
      }
      echo qixit_post_type_title($qixit_product->get_qixit_post_type());
   }
}
?>
<?php
function qixit_cross_ref_page()
{
   global $wpdb,$current_user,$wp_locale;
   $qixit_no_record_found = true;  
   qixit_date_filter_drop_down(QIXIT_PRODUCTS,'qixit_cross_ref_page');

   $qixit_products_post_ids=$wpdb->get_results( $wpdb->prepare("SELECT post_id FROM " . $wpdb->prefix.QIXIT_PRODUCTS . " 
                                                                WHERE post_qixit_PID  IS NOT NULL and qixit_post_type != %s ".qixit_date_filter_condition()." ", QIXIT_REG) );

   if ( count($qixit_products_post_ids) > 0 ) 
   { 
   ?>
   <table class="widefat"  >
      <thead>
         <tr>
            <th scope="col" width="450px" ><?php echo __('Post','qixit');?></th>
            <th scope="col" width="450px" ><?php echo __('Link','qixit');?></th>
            <th scope="col" ><?php echo __('Author','qixit');?></th>
         </tr>
      </thead>
      <tbody>
      <?php 
      foreach (  $qixit_products_post_ids  as $key => $qixit_products_post ) 
      {  $post  = get_post( $qixit_products_post->post_id );
         $owner = new WP_User( $post->post_author );
         if ( !$current_user->has_cap( "administrator" ) )
         {
            if ( $current_user->ID != $post->post_author )
            continue;
         }
         $qixit_no_record_found = false;
         ?>
            <tr valign="top">
               <td>
                  <strong>
                           <a class="row-title" href="<?php echo get_permalink($post->ID);?>">
                          <?php echo $post->post_title; ?></a>
                  </strong>
               </td>
               <td>
                  <?php echo get_permalink( $post->ID );?>
               </td>
              <td>
                <?php echo $owner->display_name; ?>
              </td>
            </tr>
         <?php
      } //endforeach
      ?>
      </tbody>
   </table>
   <?php 
   }
   else
   {
      $qixit_no_record_found = true;
   }
   
   if ($qixit_no_record_found)
   {
      ?>
      <table width="100%" >
            <tr>
               <td ><?php echo __('No data found.','qixit');?></td>
            </tr>
      </table>
      <?php
   }
}
?>
<?php
add_action( 'admin_footer', 'qixit_admin_footer_script' );
function qixit_admin_footer_script()
{
   ?>
   <script type="text/javascript" charset="utf-8">
      jQuery(document).ready(function($) {
               $('#sales_report').dataTable( {
                     "bPaginate": false,
                     "bLengthChange": false,
                     "bFilter": false,
                     "bSort": true,
                     "bInfo": false,
                     "bAutoWidth": false } 
                 );
             });
   </script>
   <?php
}

add_action( 'admin_footer', 'qixit_author_post_publishing_n_bulk_post_type_dropdown' );
/**
 * This is used to show qixit login box on author post publish and Adding drop down on edit.php for bulk action.
 */
function qixit_author_post_publishing_n_bulk_post_type_dropdown()
{
   @session_start();  
   
   //Author publishing the post         
   if ( isset($_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']) )
   {
      $post_title      = $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['post_title'];
      $post_ID         = $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['post_ID'];
      $qixit_post_type = $_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']['qixit_post_type'];
      unset($_SESSION['QIXIT_AUTHOR_PUBLISHING_POST']);
      ?>
      <script type="text/javascript">   var GB_ROOT_DIR = "<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/";</script>
      <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/AJS.js"></script>
      <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/AJS_fx.js"></script>
      <script type="text/javascript" src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/gb_scripts.js"></script>
      <link   href="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/gb_styles.css" rel="stylesheet" type="text/css" media="all" />
      <script>
      var box_height=350;
      var box_width=500;
      GB_show(   '<?php echo $post_title;?>',
               '<?php echo QIXIT_PLUGIN_URL.'/wp-qixit-author-post-publish.php?post_id='.$post_ID;?>&qixit_post_type=<?php echo $qixit_post_type;?>',
               box_height,box_width);
      </script>
      <?php
   }
   
   //Bulk post qixit dropdown add into edit.php
   if ( stristr($_SERVER['PHP_SELF'],'edit.php') )
   {
      if (  (!isset($_GET['post_status'])) ||  (isset($_GET['post_status']) && $_GET['post_status'] != 'trash') )
      {
         ?>
         <script>
         //<![CDATA[
         jQuery(document).ready(function($) 
         {  
            //Start Create Select Drop Down
            qixit_bulk_post_status_option = document.createElement('select');
            qixit_bulk_post_status_option.setAttribute('name', 'qixit_bulk_post_status_option');
            qixit_bulk_post_status_option.style.width='130px';
               
            opt=document.createElement('option');
            opt.innerHTML='<?php echo __('--Select Qixit Action--','qixit');?>';
            opt.setAttribute('value','-1');
            qixit_bulk_post_status_option.appendChild(opt);
            
            opt=document.createElement('option');
            opt.innerHTML='<?php echo qixit_post_type_title(QIXIT_REG);?>';
            opt.setAttribute('value','<?php echo QIXIT_REG;?>');
            qixit_bulk_post_status_option.appendChild(opt);
            
            opt=document.createElement('option');
            opt.innerHTML='<?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME);?>';
            opt.setAttribute('value','<?php echo QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME;?>');
            qixit_bulk_post_status_option.appendChild(opt);
            
            opt=document.createElement('option');
            opt.innerHTML='<?php echo qixit_post_type_title(QIXIT_PREMIUM_PAY_PER_VIEW);?>';
            opt.setAttribute('value','<?php echo QIXIT_PREMIUM_PAY_PER_VIEW;?>');
            qixit_bulk_post_status_option.appendChild(opt);
               
            //End Create Select Drop Down
            $('select[name="action"]').after(qixit_bulk_post_status_option);
         });      
         //]]>
         </script>
           <?php
      }
   }
}
?>
<?php
function qixit_show_errors($error_msg,$after=null)
{
   if ( !empty($error_msg) )
   {   
      echo '<div class="error qixit_admin_error"><p>';
      echo "<strong>".__('Please fix the following errors:','qixit')."</strong><br/>";
      foreach ($error_msg as $error) 
      {
         echo __($error,'qixit').'<br/>';
         echo $after;
      }
      echo '</p></div>';
   }
}
function qixit_show_success($success=null)
{
   if ( $success != null )
   {
      echo '<div id="message" class="updated fade">';
      echo '<p><strong>';
      echo __($success,'qixit');
      echo '</strong></p></div>';
   }
}

function exportcsv_from_array($results=array(),$headings=array())
{
   //--------------------------------------------------//       
   $filename = 'sales-report.csv';
   $download_box='true';
   $for_excel='false';
   //--------------------------------------------------//  
   $error_message='';
   //$csv_terminated = "|,"; // simple CSV
   //$csv_terminated = "\n"; // for_excel
   //$csv_terminated = ($for_excel=='true')?"\n":"|,";
   $csv_terminated = "\n";
   $csv_separator = ($for_excel=='true')?"\t":",";
      //$csv_enclosed = '';
       //$csv_escaped = "\\";  
   $csv_enclosed = '"';
   $csv_escaped = '"';  
   //--------------------------------------------------//
   if (!$error_message)
   {      
      if (count($results)>0)
      {
            $schema_insert = '';      
            if (!empty($headings))
            {
               $fields_name = $headings;
               $fields_cnt = count($fields_name);                  
               for ($i = 0; $i < $fields_cnt; $i++)
               {
                  $l = $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
                     str_replace('&rsquo;',"'",stripslashes($fields_name[$i])) ) . $csv_enclosed;
                  $schema_insert .= $l;
                  $schema_insert .= $csv_separator;
               } // end for
               
               $out = trim(substr($schema_insert, 0, -1));
               $out .= $csv_terminated;
            }
                           
            // Format the data
            $out1='';
            $result = array();
            for($i = 0;$i < count($results);$i++)
            {   
               $row=$results[$i];
               foreach($row as $key=>$values)
               {
                  $out1 .= $csv_enclosed . str_replace($csv_enclosed, $csv_escaped . $csv_enclosed,
                     stripslashes($row[$key])) . $csv_enclosed;
                  $out1 .= $csv_separator;
               }
               $out1 .= $csv_terminated;
            }
            $out.=$out1;
            //echo $out;

            if ($download_box=='true')
            {   
               header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
               header("Content-Length: " . strlen($out));
               // Output to browser with appropriate mime type, you choose ;
               header("Content-type: text/x-csv");
               //header("Content-type: text/csv");
               //header("Content-type: application/csv");
               header("Content-Disposition: attachment; filename=$filename");
               echo $out;
               exit;
               
            }
            else // else of if ($download_box=='true')
            {
               if (!$handle = fopen($filename, 'w')) 
               {
                  $error_message="Cannot open file ($filename)";
                   //echo "Cannot open file ($filename)";
               }
            
               // Write $out to our opened file.
               if (fwrite($handle, $out) === FALSE) 
               {
                  $error_message="Cannot write to file ($filename)";
                  //echo "Cannot write to file ($filename)";
               }
               fclose($handle);
            } // End of if ($download_box=='true')
            
      } // End of (mysql_num_rows($result)>0
      else
      {
         $error_message="Result Set is Empty";
         @unlink($filename);
      }   
   } // End of if (!$error_message)   
   else
   {
         @unlink($filename);
   }
}


function qixit_admin_options_for_authors_settings($options,$_previous)
{
         global $wpdb;
         $error_msg=array();
         $qixit_settings = get_option('qixit_settings');
       
         // cost_to_be_author
         if ( $options['cost_to_be_author'] == '' )
         {
            $options['cost_to_be_author']='0';
         }
         $number='';$number=explode('.',$options['cost_to_be_author']);
         if ( !is_numeric($options['cost_to_be_author']) || (float)$options['cost_to_be_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Readers may setup Guest Author account for a price should have non-negative numeric value with only two digit after decimal.";
         }
         else
         {   
            if ( $options['cost_to_be_author'] > 0 )
            {      
               if ( empty($error_msg) )
               {
                  if (!(qixit_registration_help_page_exists()))
                  {
                        $data=array();
                        $data['post_title']='Author Registration Help';
                        $data['post_name']='author-registration-help';
   
                        $data['post_content']='This website is using Qixit\'s 2-Way Micropay&trade; plugin. <br/><br/> This service allows you to pay small amounts for premium placement of your comments . . . or to <strong>even become an author!</strong> <br/><br/> You can register as an author, for a small fee, and then post your own articles to this blog. <br/><br/> You pay only once to post an article.  You can edit your article for free.  You can also edit or delete comments posted about your article. <br/><br/> <br/><strong>Earn Money With Your Articles</strong><br/><br/>You can insert as many links to affiliate products or your websites as you wish.<br/><br/><strong>Choosing Your ID Name</strong><br/><br/> When you <a href="'.get_option('siteurl').'/wp-login.php?action=register&type=author">create your authors\'s account</a>, please select an ID name which you want to be published with your articles. <br/><br/> <strong>PLEASE Note:</strong> We reserve the right to edit or delete your paid article placement if it violates our user agreement.  There are no refunds.';
                        $data['post_type']='page';   
                        $data['post_status']='publish';   
                        $data['comment_status']='closed';   
                        $data['ping_status']='closed';   
                        wp_insert_post($data);
                  }
                  if ( ( $_previous['qixit_settings_previous_cost_to_be_author'] != $options['cost_to_be_author'] ) || 
                           ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_registration',$qixit_settings) 
                                    && ($qixit_settings['qixit_admin_product_for_registration'] == '') ) ||
                           (  is_array($qixit_settings) && !array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
                     ) 
                  {   
                  
                     $success=qixit_product_for_author_registration($options['cost_to_be_author'], get_option('blogname'));
                     if ( !($success) )
                     {   
                       $options['cost_to_be_author']=$_previous['qixit_settings_previous_cost_to_be_author'];
                     }
                     else
                     {
                       $qixit_settings = get_option('qixit_settings');        
                       if ( array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
                       {
                         $options['qixit_admin_product_for_registration'] = $qixit_settings['qixit_admin_product_for_registration'];
                       }
                       if ( array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
                       {
                         $options['qixit_admin_product_for_author_post_publish'] = $qixit_settings['qixit_admin_product_for_author_post_publish'];
                       }
                       update_option('qixit_settings',$options); 
                     }
                  }
                }
               else
               {
                 $options['cost_to_be_author']=$_previous['qixit_settings_previous_cost_to_be_author'];
               }
            }
         }
      
         //cost_to_publish_post_by_author
         $number='';$number=explode('.',$options['cost_to_publish_post_by_author']);
         if ( !is_numeric($options['cost_to_publish_post_by_author']) || (float)$options['cost_to_publish_post_by_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Guest Authors may publish an article for a price should have non-negative numeric value only.";
         }
         else
         {
            $r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE name='".addslashes(QIXIT_AUTHOR_TAG)."'"));
            $slug=preg_replace( "/[^A-Za-z]/", "", QIXIT_AUTHOR_TAG);
            $r_set_slug = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->terms." WHERE slug='".$slug."'"));
               
            if ( count($r_set) <= 0 && count($r_set_slug) <= 0 )
            {
               $inserted   =   $wpdb->insert( $wpdb->terms, array(
                                    'name' => QIXIT_AUTHOR_TAG,
                                    'slug' => $slug
               ));
               if ( $inserted )
               {
                  $term_id=mysql_insert_id();
               }
            }
            else
            {
               $term_id=$r_set[0]->term_id;
            }
      
            $r_set = $wpdb->get_results($wpdb->prepare("SELECT *  FROM ".$wpdb->term_taxonomy." WHERE term_id ='".$term_id."'"));
            if ( count($r_set) <= 0 && $term_id )
            {
               $wpdb->insert( $wpdb->term_taxonomy, array(
                                    'term_id' => $term_id,
                                    'taxonomy'=>'post_tag',
                                    'description'=>'',
                                    'count' => '0'
                                    ));
            }
      
            if ( $options['cost_to_publish_post_by_author'] > 0 && $_previous['qixit_settings_previous_cost_to_publish_post_by_author']!=$options['cost_to_publish_post_by_author'] ||
                           ( is_array($qixit_settings) && array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) 
                                    && ($qixit_settings['qixit_admin_product_for_author_post_publish'] == '') ) ||
                           (  is_array($qixit_settings) && !array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
               )
            {      
           if ( empty($error_msg) )
           {          
                $success=qixit_product_for_author_post_publish($options['cost_to_publish_post_by_author'], get_option('blogname'));
             if ( !($success) )
             {
               $options['cost_to_publish_post_by_author']=$_previous['qixit_settings_previous_cost_to_publish_post_by_author'];
             }
            else
            {
              $qixit_settings = get_option('qixit_settings');        
              if ( array_key_exists('qixit_admin_product_for_registration',$qixit_settings) )
              {
               $options['qixit_admin_product_for_registration'] = $qixit_settings['qixit_admin_product_for_registration'];
              }
              if ( array_key_exists('qixit_admin_product_for_author_post_publish',$qixit_settings) )
              {
               $options['qixit_admin_product_for_author_post_publish'] = $qixit_settings['qixit_admin_product_for_author_post_publish'];
              }
              update_option('qixit_settings',$options); 
            }
           }
           else
           {
             $options['cost_to_publish_post_by_author']=$_previous['qixit_settings_previous_cost_to_publish_post_by_author'];
           }
            }
         }
         if ( $options['cost_to_publish_post_by_author'] == '' )
         {
            $options['cost_to_publish_post_by_author'] = '0';
         }

      
         // post_cost_of_author
         if ( $options['post_cost_of_author'] == '' )
         {
            $options['post_cost_of_author']='0';
         }
         $number='';$number=explode('.',$options['post_cost_of_author']);
         if ( !is_numeric($options['post_cost_of_author']) || (float)$options['post_cost_of_author'] < 0 || ((isset($number[1]) && strlen($number[1])>2)) )
         {
            $error_msg[]="Enable premium content settings for Authors, with a default price should have non-negative numeric value only.";
         }
         
         // percent_to_author
         if ( $options['percent_to_author'] == '' )
         {
            $options['percent_to_author']=QIXIT_DEFAUL_PERCENT_TO_AUTHOR;
         }
         if ( !is_numeric($options['percent_to_author']) || (float)$options['percent_to_author'] < 0 )
         {
            $error_msg[]="Author&rsquo;s percent share amount should have non-negative numeric value only.";
         }
         elseif ( is_numeric($options['percent_to_author']) && ( (float)$options['percent_to_author'] < 0 || (float)$options['percent_to_author'] > 100 ))
         {
            $error_msg[]="Author percent share should be between 0 and 100";
         }
         
         //if errors
         if ( empty($error_msg) && !isset($_SESSION['qixit_script_error_msg']) )
         {
            return true;      
         }
         else
         {   
            if ( isset($_SESSION['qixit_script_error_msg']) && $_SESSION['qixit_script_error_msg'] != '' )
            {
               $error_msg[] = $_SESSION['qixit_script_error_msg'];
            }
            return $error_msg;
         }
}
?>
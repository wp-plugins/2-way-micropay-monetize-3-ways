<?php
add_action( 'wp_head', 'qixit_head' );
function qixit_head()
{
   // AJS Gray Box
   $pid = '';
   if(isset($_GET['p']) && $_GET['p'] != '')
   {
         $pid = $_GET['p'];   
   } 
   ?>
   <script type="text/javascript"> var GB_ROOT_DIR = "<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/";</script>
   <script
     type="text/javascript"
     src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/AJS.js"></script>
   <script
     type="text/javascript"
     src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/AJS_fx.js"></script>
   <script
     type="text/javascript"
     src="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/gb_scripts.js"></script>    
   <link
     href="<?php echo QIXIT_PLUGIN_URL;?>/js/greybox/gb_styles.css"
     rel="stylesheet" type="text/css" media="all" />
   <?php
}
?>
<?php
add_filter('comment_reply_link', 'qixit_comment_reply_link'); 
function qixit_comment_reply_link($link)
{				
		$qixit_new_link = explode('replytocom', $link);	
		$qixit_pre_id = explode('#',$qixit_new_link[1]);
		$id = substr($qixit_pre_id[0], 1, 99);
		$qixit_all_settings = qixit_all_settings_details();
		$qixit_comments_data = get_comments_data($id);		
		
		if ($qixit_all_settings['paid_comments']=='1')
		{
			if($qixit_comments_data == 'REGULAR'){
				$link='';
			}
			if(isset($_GET['commentbox']) && $_GET['commentbox'] != ''){	
				if($qixit_comments_data != 'PREMIUM' && $qixit_commentbox == 'true'  ){
					$link = '';	
				}	
			}
		}

	return $link;
}
// check what should be returned when clicking on more link..
add_filter('the_content', 'qixit_add_more_page_link');
function qixit_add_more_page_link($content)
{  

   @session_start();
   global $more,$current_user;
   $post_id = get_the_id();
   $post = get_post($post_id);
   $qixit_settings = get_option( 'qixit_settings' );
   
   if ($post->post_type=='post')
   {
      $meta=get_post_meta($post_id,'_qixit_delivery_url');
      if ( !empty($meta))
      {
       return $content;
      }
      
      if ( $current_user->has_cap( "administrator" ) || $current_user->has_cap( "editor" ) )
      {   
          return $content;
      }
      
      
   
      $more_tag_html_single_page = '<span id="more-'.$post_id.'"></span>';
      $more_tag_position = (strpos($content, $more_tag_html_single_page) <= 0 )?$qixit_settings['characters']:strpos($content, $more_tag_html_single_page);
       
      $more_tag_html_post_list_page = 'is_viewable';
      $more_tag_position_on_post_list_page = strpos($content, $more_tag_html_post_list_page);
      $qixit_more_tag = substr($content, 0, $more_tag_position)."  <a class='more-link qixit_more_link_on_post_list_page' 
      href='JavaScript:void(0)' onclick='is_viewable(\"".get_permalink($post_id)."\")'  >".__(QIXIT_MORE_LINK,'qixit')."</a>";
         
      if ( qixit_display_full_content() )
      {
         return $content;
      }
      $qixit_product = new QixitProduct($post_id);
      if ($more && $qixit_product->get_post_qixit_PID() && $qixit_product->get_premium_post_cost() > 0)
      {
         if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_ONCE_VIEW_ANYTIME)
         {
            if (qixit_is_cookie_set($post_id))
            {
               return $content;
            }
            else
            {
               return $qixit_more_tag;
            }
         }
         else if ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW)
         {
            if (isset($_SESSION['paid_for_one_time']))
            {
               return $content;
            }
            else
            {
               return $qixit_more_tag;
            }
         }
         return $content;
      }
      if ( !$more && $more_tag_position_on_post_list_page<=0 )
      {
         if ($qixit_product->get_qixit_post_type() == QIXIT_REG)
         {
            return $content;
         }
         else
         {
            return $qixit_more_tag;
         }
      }
   }
   elseif ($post->post_type=='page')
   {   
      if (stristr($content,'[QIXIT_AMOUNT_SHARE]'))
      {
         return str_replace('[QIXIT_AMOUNT_SHARE]',$qixit_settings['percent_to_author'],$content);
      }
   }
   return $content;
}

// modify the more link, so that on click, we can check if the content is viewable or not
add_action( 'the_content_more_link', 'qixit_updated_the_content_more_link' );
function qixit_updated_the_content_more_link($more_link)
{
   global $post, $more_link_text;

   $old_link = $more_link;
   $end_pos = stripos($old_link,'</a>');
   $old_link = substr($old_link,0,$end_pos);
   $start_pos = stripos($old_link,'>');
   $old_link = substr($old_link,$start_pos+1);
   $more_link_text = $old_link_text = $old_link;

   //return our style $more link;
   return $new_more_link='<a href="JavaScript:void(0)" class="more-link" onclick="is_viewable(\''.get_permalink($post->ID).'\')">'.$old_link_text.'</a>';
}

add_action( 'wp_print_scripts', 'qixit_enqueue_scripts' );
function qixit_enqueue_scripts()
{
   wp_enqueue_script('jquery');
}

add_action( 'comments_array', 'qixit_comments_array' );
function qixit_comments_array($comments_array)
{
   global $wpdb;
   @session_start();
   $regular_comments = array();
   $premium_comments = array();
   foreach($comments_array as $key=>$comment_object)
   {
      if ($comment_object->qixit_comment_type == QIXIT_REGULAR)
      {
         $regular_comments[] = $comment_object;
         unset($comments_array[$key]);
      }
      else
      {
         $premium_comments[] = $comment_object;
      }
   }
   //put the comments on session
   $_SESSION['PREMIUM_COMMENTS'] = $premium_comments;
   $_SESSION['REGULAR_COMMENTS'] = $regular_comments;
    
   // modify the comments_array to only contain regular comments.
   $comments_array = array_merge($comments_array, $regular_comments);
   return $comments_array;
}

add_action('comments_popup_link_attributes','qixit_comments_popup_link_attributes');
function qixit_comments_popup_link_attributes()
{
   return "onclick=\"JavaScript:qixit_session_marker_to_no_qixit_login(this)\"";
}

//pre_comment_on_post
add_action('preprocess_comment','qixit_preprocess_comment');
function qixit_preprocess_comment($comment_data)
{   
   @session_start();
   global $wpdb;
   $qixit_allow_premium_comments = true;
   $qixit_settings = get_option( 'qixit_settings' );
   if ($comment_data['comment_parent'] != 0)
   {
      $comment_parent = get_comment($comment_data['comment_parent']);
      if ($comment_parent->qixit_comment_type == QIXIT_REGULAR)
      {
         $qixit_allow_premium_comments = false;
         unset($_SESSION['QIXIT_POST_COMMENTS_DATA']);
      }
   }
       
   if ($qixit_settings['paid_comments'] == '0')
   {
      $qixit_allow_premium_comments = false;
      unset($_SESSION['QIXIT_POST_COMMENTS_DATA']);
   }

   if ( (!(isset($_POST['qixit_comment_type']))) && $qixit_allow_premium_comments)
   {   
      $comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;
      $qixit_product = new QixitProduct($comment_post_ID);

      if ($qixit_allow_premium_comments && $qixit_product->get_comments_qixit_PID() != '' )
      {
         // premium comments is only on posts created by admin, so check who is the owner first
         $comment_post = get_post( $comment_post_ID );
         $user = new WP_User( $comment_post->post_author );
         $_SESSION['QIXIT_POST_COMMENTS_DATA'] = $_POST;
         $permalink = get_permalink($comment_post_ID);
         
         if (stristr($permalink,'?'))
         {
            wp_redirect($permalink . '&commentbox=true');
         }
         else
         {
            wp_redirect($permalink . '?commentbox=true');
         }
         die(" "); // do not delete this otherwise control will not redirect.
      }
   }
   return $comment_data;
}

add_action('comment_post','qixit_comment_post');
function qixit_comment_post($comment_ID)
{
   global $wpdb,$current_user;
   @session_start();

   if (isset($_SESSION['QIXIT_POST_COMMENTS_DATA']))
   {
      $qixit_product = new QixitProduct($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_post_ID']);
      $qixit_settings = get_option('qixit_settings');
       

      $comment = get_comment($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent']);
      if ( ($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent'] == 0) || ((!empty($comment)) && $comment->qixit_comment_type == QIXIT_PREMIUM) && $comment_ID > 0)
      {
         if ( (!(isset($_SESSION['QIXIT_POST_COMMENTS_DATA']['QIXIT_COMMENT_TYPE'])
         && $_SESSION['QIXIT_POST_COMMENTS_DATA']['QIXIT_COMMENT_TYPE'] == QIXIT_REGULAR)) && isset($_POST['qixit_comment_type']) )
         {
            // approve the comment, since its a premium comment
            wp_update_comment(array('user_id'=>$current_user->ID,'user_ID'=>$current_user->ID,'comment_ID'=>$comment_ID,'comment_approved'=>1));
            // update the qixit_comment_type
            $wpdb->update( $wpdb->comments,
            array(
                  'qixit_comment_type' => QIXIT_PREMIUM,
            ),
            array(
                  'comment_ID' => $comment_ID,
            )
            );

            // insert payment information for comment purchase
            $wpdb->insert( $wpdb->prefix.QIXIT_PAYMENT_DETAILS, array(
               'product_id' => $qixit_product->get_product_id(),
               'qixit_PID' => $qixit_product->get_comments_qixit_PID(),
               'qixit_id' => (($_SESSION['QIXIT_POST_COMMENTS_DATA']['qixit_id']!='')?$_SESSION['QIXIT_POST_COMMENTS_DATA']['qixit_id']:''),
               'total' => $qixit_product->get_premium_comments_cost(),
               'payment_for' => 'comments',
               'percent_to_author' => $qixit_product->get_percent_to_author(),
               'date_purchased' => date('Y-m-d H:i:s')
            ));
         }
      }
   }
   unset($_SESSION['QIXIT_POST_COMMENTS_DATA']);
}

function qixit_display_full_content()
{
   global $post;
   $current_user = wp_get_current_user();

   if ( is_user_logged_in() )
   {
      if ( is_admin() || $post->post_author == $current_user->ID)
      {
         return true;
      }
      return false;
   }
   return false;
}


add_action( 'wp_footer', 'qixit_footer' );
function qixit_footer()
{
   global $post, $posts, $more;
   @session_start();
   $qixit_product = new QixitProduct($post->ID);
   $qixit_ad_hoc_product = new QixitAdHocProduct($post->ID);
   
   // for post
   if ( $more && $qixit_product->get_product_id()!='' && ($qixit_product->get_post_qixit_PID() != '') )
   {
      if ( ($qixit_product->get_qixit_post_type() == QIXIT_PREMIUM_PAY_PER_VIEW) && !qixit_display_full_content())
      {
         if ((isset($_SESSION['paid_for_one_time'])))
         {
            unset($_SESSION['paid_for_one_time']);
         }
      }
   }   
   // for ad-hoc-product
   if ( $more && $qixit_ad_hoc_product->get_product_id()!='' && ($qixit_ad_hoc_product->get_qixit_PID() != '') )
   {   
      if ( ($qixit_ad_hoc_product->get_qixit_ad_hoc_link_type() == QIXIT_PREMIUM_PAY_PER_VIEW) && !qixit_display_full_content())
      {
         if ((isset($_SESSION['paid_for_one_time'])))
         {
            unset($_SESSION['paid_for_one_time']);
         }
      }
   }   
   // for comments
   if (isset($_GET['commentbox']) && ($_GET['commentbox'] == true))
   {   
      qixit_show_pre_comments_box();
   }
   
   if(isset($_SESSION['QIXIT_POST_COMMENTS_DATA'])){
	 qixit_refill_comments_data();
	}
}

add_action( 'login_head', 'qixit_author_registraiton_css');
function qixit_author_registraiton_css()
{
   wp_register_style('qixit', QIXIT_PLUGIN_URL.'/css/style.css', array(), QIXIT_VERSION, 'all' );
   wp_print_scripts('jquery');
   wp_admin_css('qixit',true);
   ?>
   <script type="text/javascript"> 
   //<![CDATA[  
            jQuery(document).ready(function($) 
            { 
                  $('#reg_passmail').html('');
            });
   //]]>                  
   </script>
   <?php
}




add_action('register_post','qixit_check_registration_fields',1,3);
function qixit_check_registration_fields($login, $email, $errors)
{
   global $user_password, $user_password_confirm;
   if ( get_option( 'default_role' )=='author' || ( isset($_POST['type']) && $_POST['type']=='author' ) )
   {   
      $qixit_settings = get_option( 'qixit_settings' );
      if ($qixit_settings['cost_to_be_author']>0)
      {
         if ( $_POST['user_password'] == '' ) 
         {
            $errors->add('empty_user_password', '<strong>'.__('ERROR','qixit').'</strong> : '.__('Please enter password','qixit'));
         } 
         else 
         {
            $user_password = $_POST['user_password'];
         }
         if ( $_POST['user_password_confirm'] == '' ) 
         {   
            $errors->add('empty_user_password_confirm', '<strong>'.__('ERROR','qixit').'</strong> : '.__('Please enter confirm password','qixit'));
         } 
         else 
         {
            $user_password_confirm = $_POST['user_password_confirm'];
         }
         if ( $_POST['user_password_confirm'] != $_POST['user_password'] ) 
         {   
            $errors->add('empty_user_password_n_confirm_equal', '<strong>'.__('ERROR','qixit').'</strong> : '.__('Password & confirm password should be same','qixit'));
         } 
      }
   }
}

add_action('register_post','qixit_author_register', '2', 3);
function qixit_author_register($user_login, $user_email, $errors)
{   
   @session_start();
   if ( get_option( 'default_role' )=='author' || ( isset($_POST['type']) && $_POST['type']=='author' ) )
   {  
      $qixit_settings = get_option( 'qixit_settings' );
      if (empty($errors->errors) && $qixit_settings['cost_to_be_author']>0)
      {   
         $_SESSION['QIXIT_AUTHOR_DATA']=$_POST;
         wp_redirect(get_option('siteurl').'/wp-login.php?action=register&type=author');
        die();
      }
   }
}


add_action( 'register_form', 'qixit_atuhor_registration_extra_fields' );
function qixit_atuhor_registration_extra_fields()
{	
	$qixit_settings = get_option( 'qixit_settings' );
	if($qixit_settings['e_guest_authors'] == 0){	
		$site_url = get_option('siteurl');
		echo "<script> window.location.href='$site_url/wp-login.php?registration=disabled'</script>";
	}else{
	
   if ( get_option( 'default_role' )=='author' || ( isset($_REQUEST['type']) && $_REQUEST['type']=='author' ) )
   {
      $qixit_settings = get_option( 'qixit_settings' );
      if ($qixit_settings['cost_to_be_author']>0)
      {
      ?>
          <input type="hidden" name="type" id="type" value="author" class="input" value="" size="25" tabindex="20" />
      <p>
         <label><?php echo __('Password','qixit') ?><br />
         <input type="password" name="user_password" id="user_password" class="input" value="" size="25" tabindex="20" /></label>
      </p>
      <p>
         <label><?php echo __('Confirm Password','qixit') ?><br />
         <input type="password" name="user_password_confirm" id="user_password_confirm" class="input" value="" size="25" tabindex="20" /></label>
      </p>
      <?php
      }
   }
	}
}

/**
 * when registration form is posted and validation is complete and control is ready to create user, we redirect to registration form.
 * We use SESSION['QIXIT_AUTHOR_DATA'] and display qixit login box. After qixit login user is redirected to
 * delivery page and registration is completed.
 */ 
add_action( 'register_form', 'qixit_show_login_box_on_author_registration' );
function qixit_show_login_box_on_author_registration()
{
   @session_start();
   if (isset($_SESSION['QIXIT_AUTHOR_DATA']) && isset($_GET['type']) && ($_GET['type'] == 'author'))
   {
        $user_login = $_SESSION['QIXIT_AUTHOR_DATA']['user_login'];
        $user_email = $_SESSION['QIXIT_AUTHOR_DATA']['user_email'];
        $user_password = $_SESSION['QIXIT_AUTHOR_DATA']['user_password'];
        unset($_SESSION['QIXIT_AUTHOR_DATA']);
        qixit_show_author_registration($user_login, $user_email,$user_password);
   }
}


add_action( 'wp_footer', 'qixit_footer_script' );
function qixit_footer_script()
{ 
   @session_start();
   global $post, $posts, $more;
   $qixit_settings = get_option( 'qixit_settings' );
   ?>
   <script type="text/javascript"> 
   //<![CDATA[   
    
      function is_viewable(permalink)
      {         
         permalink_without_http = permalink.replace(/(https|http):\/\//gi, "");
         //permalink_without_http = permalink; 
         jQuery(document).ready(function($) { 
            $.ajax({
                  type: "GET",
                  url: "<?php echo QIXIT_PLUGIN_URL;?>/wp-qixit-ajax-req.php?action=is_viewable&permalink=" + permalink_without_http,
                  dataType: "json",
                  success: function(data){
                      if (data['result'] == 'SUCCESS')
                      {   
                         if (data['qixit_product_type']=='P')
                         {
                              window.location = permalink;
                         }
                         else if (data['qixit_product_type']=='A')
                         { 
                              window.location=data['durl'];
                         }                  
                      }
                      else 
                      {   
                        show_qixit_login_box(data['post_id'],data['post_title']);
                      }
                  }
               });
            });
      }

      function show_qixit_login_box(post_id,post_title)
      {   
         var box_height=350;
         var box_width=450;   
         GB_show(post_title,'<?php echo QIXIT_PLUGIN_URL."/wp-qixit-login.php?post_id=";?>'+post_id, box_height, box_width);
      }

      jQuery(document).ready(function($) 
      {   
      
        $('a:[rel="bookmark"]').each(function (i) 
        {      
              //alert(this.href);     
              var permalink=this.href;
              this.href='JavaScript:void(0)';
              this.id='qixit_title_link_'+i;    
              $('#qixit_title_link_'+i).click(function()
              {      
                  is_viewable(permalink)
              });            
                            
        });        
        
       // display heading over free and premium comments        
      <?php
      global $wp_version, $more_link_text;
      
      // To Add PREMIUM comment heading on post single page
      if (isset($_SESSION['PREMIUM_COMMENTS']) && !empty($_SESSION['PREMIUM_COMMENTS']))
      {
         if (version_compare($wp_version, '3.0') >= 0)
         {	
            $i=1;
            foreach($_SESSION['PREMIUM_COMMENTS'] as $key=>$comment_object)
            {      
               if ($i=='1')
               { 
                  ?>
                  //code for wp-3.0 theme
                  if ($("#li-comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {   
                     $('<li class="qixit_comments_title_premium"><?php echo __('Premium Comments','qixit');?></li>').insertBefore("#li-comment-<?php echo $comment_object->comment_ID;?>");
                  }
                  //
                  //code for wp-2.9.2 or < wp-3.0 theme
                  if ($("#comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {
                   $('<li class="qixit_comments_title_premium"><?php echo __('Premium Comments','qixit');?></li>').insertBefore("#comment-<?php echo $comment_object->comment_ID;?>");
                  }
                  //
                  <?php
               }
               ?> 
                  //code for wp-3.0 theme
                  $("#li-comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_premium_comments');
                  $("li.comment").css("margin","0px");
                  $(".qixit_comments_title_premium").css("background-color","#<? echo $qixit_settings['premium_heading_bg_color'];?>");
                  $("#li-comment-<?php echo $comment_object->comment_ID;?>").css("background-color","#<? echo $qixit_settings['premium_bg_color'];?>");
                  //
                  //code for wp-2.9.2 or < wp-3.0 theme
                  $("#comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_premium_comments');
                  $("li.comment").css("margin","0px");
                  $(".qixit_comments_title_premium").css("background-color","#<? echo $qixit_settings['premium_heading_bg_color'];?>");
                  $("#comment-<?php echo $comment_object->comment_ID;?>").css("background-color","#<? echo $qixit_settings['premium_bg_color'];?>");
                  //
               <?php
            $i++;}
         }
         else
         {   
            $i=1;
            foreach($_SESSION['PREMIUM_COMMENTS'] as $key=>$comment_object)
            {
               if ($i=='1')
               {
                  ?>
                  if ($("#comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {
                   $('<li class="qixit_comments_title_premium"><?php echo __('Premium Comments','qixit');?></li>').insertBefore("#comment-<?php echo $comment_object->comment_ID;?>");
                  }
                  <?php
               }
               ?>	
                  $("#comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_premium_comments');
                  $("li.comment").css("margin","0px");
                  $(".qixit_comments_title_premium").css("background-color","#<? echo $qixit_settings['premium_heading_bg_color'];?>");
                  $("#comment-<?php echo $comment_object->comment_ID;?>").css("background-color","#<? echo $qixit_settings['premium_bg_color'];?>");
               <?php
            $i++;}
         }   
            unset($_SESSION['PREMIUM_COMMENTS']);
      }
      
      // To Add REGULAR comment heading on post single page
      if (isset($_SESSION['REGULAR_COMMENTS']) && !empty($_SESSION['REGULAR_COMMENTS']))
      {
         if (version_compare($wp_version, '3.0') >= 0)
         {
            $i=1;
            foreach($_SESSION['REGULAR_COMMENTS'] as $key=>$comment_object)
            {
               if ($i=='1')
               {
                  ?>
                  //code for wp-3.0 theme
                  if ($("#li-comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {
                     $('<li class="qixit_comments_title_regular"><?php echo __('Free Comments','qixit');?></li>').insertBefore("#li-comment-<?php echo $comment_object->comment_ID;?>");
                  }
                  //
                  //code for wp-2.9.2 or < wp-3.0 theme
                  if ($("#comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {
                     $('<li class="qixit_comments_title_regular"><?php echo __('Free Comments','qixit');?></li>').insertBefore("#comment-<?php echo $comment_object->comment_ID;?>");
                  }
               <?php
               }
               ?>
                  //code for wp-3.0 theme
                  $("#li-comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_free_comments');
                  $("li.comment").css("margin","0px");
                  //
                  $("#comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_free_comments');
                  $("li.comment").css("margin","0px");
                  //code for wp-2.9.2 or < wp-3.0 theme
            <?php
            $i++;}
         }
         else
         {   
              $i=1;
            foreach($_SESSION['REGULAR_COMMENTS'] as $key=>$comment_object)
            {      
               if ($i=='1')
               {
                  ?>
                  if ($("#comment-<?php echo $comment_object->comment_ID;?>").hasClass('depth-1'))
                  {
                     $('<li class="qixit_comments_title_regular"><?php echo __('Free Comments','qixit');?></li>').insertBefore("#comment-<?php echo $comment_object->comment_ID;?>");
                  }
               <?php
               }
               ?>
                  $("#comment-<?php echo $comment_object->comment_ID;?>").addClass('qixit_free_comments');
                  $("li.comment").css("margin","0px");
            <?php
            $i++;}
         }   
         unset($_SESSION['REGULAR_COMMENTS']);
      }
      
      // To Add More link text in character based more link.
      if ($more_link_text!='')
       {
          ?>
          $('a:[class="more-link qixit_more_link_on_post_list_page"]').each(function (i) 
          {      
           $(this).html('<?php echo $more_link_text;?>')
          });   
         <?php
       }
       ?>
       
     });
   

   function qixit_session_marker_to_no_qixit_login(comment_link)
   {
      location_to_comments=comment_link.href;
      comment_link.href='JavaScript:void(0)';      
      jQuery(document).ready(function($) 
      {
         $.ajax({
               type: "GET",
               url: "<?php echo QIXIT_PLUGIN_URL;?>/wp-qixit-ajax-req.php?action=session_marker_to_no_qixit_login",
                     dataType: "json",
                     success: function(data){
                           window.location=location_to_comments;
                     }
                  });
      });
   }
   //]]>                  
   </script>
   <?php
   
   
   if ( $more && qixit_is_ad_hoc_product($post->ID) == true ) 
   {    
      ?>
      <script type="text/javascript"> 
      //<![CDATA[   
            is_viewable('<?php echo get_permalink($post->ID);?>');
      //]]>
      </script>
      <?php
   }
}



   

?>
<?php
function qixit_refill_comments_data(){
	if(isset($_SESSION['QIXIT_POST_COMMENTS_DATA'])){
		if(isset($_GET['p']) && $_GET['p'] != ''){
			$comment_post_ID = $_GET['p'];
		}else{
			$comment_post_ID = $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_post_ID'];
		}
		?>	
        	<script>
      jQuery(document).ready(function($) 
      {
          $('#author').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['author'];?>');
          $('#email').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['email'];?>');
          $('#url').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['url'];?>');
          $('#comment').val('<?php echo html_entity_decode(esc_js(stripslashes($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment'])));?>');
          $('#comment_post_ID').val('<?php echo $comment_post_ID;?>');
          $('#comment_parent').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent'];?>');
      });
   </script>
        <?php
	}	
}

function qixit_show_pre_comments_box()
{
   ?>
   <script>
      jQuery(document).ready(function($) 
      {
          $('#author').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['author'];?>');
          $('#email').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['email'];?>');
          $('#url').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['url'];?>');
          $('#comment').val('<?php echo html_entity_decode(esc_js(stripslashes($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment'])));?>');
          $('#comment_post_ID').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_post_ID'];?>');
          $('#comment_parent').val('<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent'];?>');
      });
   </script>
   <script>
      var box_height=620;
      var box_width=600;
      GB_show('Comments','<?php echo QIXIT_PLUGIN_URL."/wp-qixit-comments-box.php";?>', box_height, box_width);
   </script>
   <?php
}
?>
<?php
function qixit_show_author_registration($user_login, $user_email,$user_password)
{
   @session_start();
   $_SESSION['AUTHOR_REGISTRATION']['user_password'] = $user_password;
   ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title></title>
   <?php qixit_head();?>
</head>
<body>
</body>
</html>
   <?php
   //AJS Grey Box
   ?>
   <script> 
      var box_height=300;
      var box_width=400;
      GB_show('Author Registration','<?php echo QIXIT_PLUGIN_URL.'/wp-qixit-author-registration.php?action=register&user_login='.$user_login.'&user_email='.$user_email;?>',box_height,box_width);
   </script>
<?php
}
?>
<?php
function qixit_front_show_errors($error_msg=null)
{
   if (is_array($error_msg) && (!empty($error_msg)) )
   {
      ?>
      <div id="front_error_message" class="updated fade">
      <p class="qixit_front_error_p"><?php   
      echo "<strong>".__('Please fix the following errors:','qixit')."</strong><br/>";
      foreach ($error_msg as $key=>$error_value)
      {
         if (is_array($error_value))
         {
            foreach ($error_value as $error) echo __($error,'qixit').'<br/>';
         }
         else
         {
            echo __($error_value,'qixit').'<br/>';
         }
      }
?></p>
</div>
<?php
   }
   elseif (is_string($error_msg))
   {
      ?>
<div id="message" class="updated fade">
<p><?php
echo __($error_msg,'qixit');
?></p>
</div>
<?php
   }
}
?>
<?php
function qixit_front_show_success($success=null)
{
   if ( $success != null )
   {
     ?>
     <div id="front_success_message">
       <p><strong><?php echo __($success,'qixit'); ?></strong></p>
     </div>
     <?php
   }
}
?>
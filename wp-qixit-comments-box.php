<?php
define('DOING_AJAX', false);
require_once('../../../wp-load.php');
@session_start();
$error='';
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
wp_print_scripts('jquery');
qixit_wp_head_include();
wp_print_styles('qixit');
$qixit_show_free_comment_link = true;
if ($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent'] > 0)
{
   $comment = get_comment($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent']);
   if (!(empty($comment)) && $comment->qixit_comment_type == 'PREMIUM')
   {
      $qixit_show_free_comment_link=false;
   }
}

?>
<div class="qixit_normal_text">
   <script>
   //<![CDATA[
   function qixit_comment_submit_as_regular()
   {   jQuery(document).ready(function($) {
               $.ajax({
                     type: "GET",
                     url: "<?php echo QIXIT_PLUGIN_URL;?>/wp-qixit-ajax-req.php?action=session_marker_to_regular_comment",
                     dataType: "json",
         success: function(data)
         {
                     window.parent.parent.location='<?php echo QIXIT_PLUGIN_URL . "/wp-qixit-comment-post.php";?>';                    					 
         }
                  });

      });
   }
   //]]>
   </script>
   <div class="qixit_comments_box_content">
      <?php 
	  	 	$qixit_all_setting = qixit_all_settings_details();
			$paid_comments_only = $qixit_all_setting['paid_comments_only'];
			
			if($paid_comments_only == 1){
				$qixit_show_free_comment_link = false;
			}
         if($qixit_show_free_comment_link === true) 
         {
            ?> 
            
            <h2><?php echo __('Free Comment','qixit');?></h2>
            <input type="radio"   name="qixit_comments_type" value="REG"   onclick="
                  if(this.checked == true) 
                  {
                      document.getElementById('post_comments').style.display='none';
                      document.getElementById('go_to_submit').style.display='block';
                  }
                  " />
            <?php 
              echo __('Free Comment Placement - below premium comments','qixit');
            ?>
               <div id='go_to_submit' style="margin-left:300px;"  >
                 <a href="JavaScript:void(0)" class="qixit_free_comment_submit" onclick="qixit_comment_submit_as_regular()">
                    <span class="qixit_free_comment_submit_span"><?php echo __('Submit','qixit');?></span>
                 </a>
                 <br/>
               </div>
            <?php 
         } 
      ?>
      <br />
      <h2><?php echo __('Premium Comment','qixit');?></h2>
      <input type="radio" name="qixit_comments_type" value="PREMIUM"
      <?php
      $checked="checked='checked'";
      if($qixit_show_free_comment_link === false)
      {
         echo $checked;
      }
      else
      {
      ?>
      checked="checked"
      onclick="if(this.checked==true)
         {
            document.getElementById('post_comments').style.display='block';
            document.getElementById('go_to_submit').style.display='none';
         }"
      <?php
      }
      ?> />
      <?php echo __('Premium Placement - above free comments','qixit');   ?>
      <a href="<?php echo QIXIT_LEARN_MORE_LINK;?>" target="_blank"><?php echo __('Learn More','qixit');?></a>
      <br />
   </div>
   
   <?php
   $qixit_settings = get_option('qixit_settings');
   
   //Comment Product Purchase
   $qixit_product = new QixitProduct($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_post_ID']);
   $qixit_comment_product_purchase_object = new PurchaseFast();
   $qixit_comment_product_purchase_object->set_qixit_pid($qixit_product->get_comments_qixit_PID());
   ?> 
   <iframe id="post_comments" src="<?php echo $qixit_comment_product_purchase_object->qixit_construct_url();?>" width="80%" height="200px" scrolling="no" frameborder="0px" style="border: 0px; display: none;"></iframe>
   <div class="qixit_comments_box_content">
      <br />
      <a href="<?php echo QIXIT_FREE_MONEY_LINK;?>" target="_blank"><?php echo __('New? Your free account comes with &quot;free&quot; money!','qixit');?></a>
      <br />
      <p>
         <?php echo __('It costs just','qixit');?> &nbsp;<?php echo round($qixit_product->get_premium_comments_cost(),2);?>&nbsp;
         <?php echo __('cents to buy','qixit');?><i><?php echo __(' immediate,','qixit');?></i>
         <?php echo __('premium placement of your comment.','qixit');?>
      </p>
      <p><strong>
      <?php echo __('Benefits of Premium Placement:','qixit');?></strong></p>
      <ol>
         <li><?php echo __('Immediate Posting. No waiting for review.'); ?></li>
         <li><?php echo __('Permanent placement above all free comments.'); ?></li>
         <li><?php echo __('Premium commentors may earn more &quot;free&quot; money.'); ?></li>
      </ol>
      <br />
      <a href="JavaScript:void(0)" onclick="show_hide('terms_of_service')" >
            <?php echo __('Terms of Service For Posting of Premium Comments','qixit');   ?>
      </a>
      <div id='terms_of_service'><?php echo nl2br(stripslashes($qixit_settings['terms_of_service'])); ?></div>
   </div>
   <script>
         jQuery(document).ready(function($) 
         { 
            if ( ($('input[type="radio"][name="qixit_comments_type"][checked="checked"]').val()=='PREMIUM')
            || $('input[type="radio"][name="qixit_comments_type"][checked="checked"]').val()==undefined )
            {
               $('#post_comments').show();
               $('#go_to_submit').hide();   
            }
            else
            {
               $('#post_comments').hide();
               $('#go_to_submit').show();
            }
         });
   </script>   
</div>
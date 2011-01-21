<div class="wrap">
<h2><?php echo __('QixIT Micropay Settings','qixit');?></h2>
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
<script>
//<![CDATA[ 
   jQuery(document).ready(function($) 
   { 
   $('<a href="JavaScript:void(0)" id="passwordUpdate" class="passwordmask"><?php echo __('Update password','qixit');?></a>').insertAfter($('#qixit_password')).click(function(){
      var password = $('#qixit_password');
      if (password.css('display')=='block' || password.css('display')=='inline' || password.css('display')=='inline-block')
      {
         $('#qixit_password_mask').html('*****');
         password.hide();
         $('#passwordUpdate').text('<?php echo __('Update password','qixit');?>');
      }
      else
      {   
         $('#qixit_password_mask').html('');
         password.show();
         $('#passwordUpdate').text('<?php echo __('Hide password','qixit');?>');
      }
      return false;
      });
   });
//]]></script>
<form method="post" action="">
<?php 
   if ( !is_array($options) || !array_key_exists('widget_name',$options))
   {
      ?>
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden"  id="qixit_settings[widget_name]"   name="qixit_settings[widget_name]"   value="We Publish Your Articles" /> 
      <?php
   }
   else
   {
      ?>
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden"  id="qixit_settings[widget_name]"   name="qixit_settings[widget_name]"   value="<?php echo $options['widget_name'];?>" /> 
      <?php
   }
?>
<table class="form-table">
   <tr valign="top">
     <th scope="row" colspan="2" style="width: 100%"><strong><?php echo __('NOTE ON ACCOUNT RESTRICTIONS:','qixit');?></strong><br />
        <?php echo __('For the present time, 2-Way Micropay&#0153; and Qixit&#0153; are only for use by persons within the United States.','qixit');?>
     </th>
   </tr>
   <tr valign="top">
     <th scope="row" colspan="2" style="width: 100%"><strong><?php echo __('ACCOUNT SETUP INSTRUCTIONS:','qixit');?></strong><br />
        <?php echo __('This plug in will automatically create and manage your product purchase links. To use 2-Way Micropay&#0153; you must first create a Qixit account at','qixit'); echo' <a href="http://www.qixit.com/info/wpsetup.htm" target="_blank">www.Qixit.com</a> ';

 echo __('to send and receive funds. Secondly, you must then setup a vendor account at','qixit'); 
 
 echo ' <a href="http://www.qixit.com/info/vendor.htm" target="_blank">http://www.qixit.com/info/vendor.htm</a>. '; 
 
 echo __('After you complete the vendor account setup, you will be able to create 2-Way Micropay product links to collect payments when users click on your product links.   After you have completed these two steps, complete the information in the form below to select how 2-Way Micropayments will work in your blog.','qixit');?>
     </th>
   </tr>
  <tr valign="top">
       <th scope="row" colspan="2" style="width: 100%"><strong><?php echo __('NOTE ON ADMINISTRATOR PAYMENTS:','qixit');?></strong><br />
          <?php echo __('When logged in as an Administrator, you can browse directly to any payment link <u>without making a payment.</u>  Remember this if you are trying to check out the payment system or Sales History.  ','qixit');?>
     </th>
   </tr>
   <tr valign="top">
      <th scope="row" colspan="2" style="width: 100%">
          <strong><?php echo __('Qixit Vendor Account:', 'qixit'); ?></strong>
      </th>
   </tr>
   
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __('Qixit ID','qixit');?></th>
      <td style="margin-left: 15px;">
          <input STYLE="background-color: #d0d0d0;border-color:black;" type="text" name="qixit_settings[qixit_id]"
            value="<?php echo $options['qixit_id']; ?>" />
      </td>

   
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;""><?php echo __('Qixit Product Creation Password','qixit');?></th>
      <td>
          <?php
          if ($options['qixit_password'] == '')
          {
          ?> 
             <span id='qixit_password_mask'></span> 
                <input STYLE="background-color: #d0d0d0;border-color:black;"type="password"   id="qixit_settings[qixit_password]"   
                   name="qixit_settings[qixit_password]"   
                   value="<?php echo base64_decode($options['qixit_password']); ?>" /> 
       <?php 
          } 
          else 
          { 
       ?>
             <span id='qixit_password_mask'>*****</span> 
             <input STYLE="background-color: #d0d0d0;border-color:black;"type="password"   id="qixit_password" 
               name="qixit_settings[qixit_password]" 
               value="<?php echo base64_decode($options['qixit_password']); ?>"
               style="display: none" /> 
          <?php 
          }
          ?>
           <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" name="qixit_settings_previous_password"   value="<?php echo (is_array($options))?base64_decode($options['qixit_password']):''; ?>" />
      </td>
   </tr>

   <tr valign="top">
      
   	  <tr />
      <th scope="row" colspan="2" style="width: 100%">
          <strong><?php echo __('Premium Content:', 'qixit'); ?></strong>
      </th>
   </tr>
       </tr>
      <tr valign="top">
      <th scope="row" colspan="2" style="width: 100%">
       
              <?php echo __('All amounts are in dollars and cents, i.e., 5 cents should be entered as .05.','qixit');?>
         
     
      </th>
   </tr> 
   <tr valign="top">
      
   	  <th scope="row" style="margin-left: 15px;"><?php echo __('Enable premium content, with a default price of','qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" name="qixit_settings[cost]"   value="<?php echo (is_array($options))?$options['cost']:QIXIT_DEFAUL_COST; ?>" /> 
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" name="qixit_settings_previous_cost"   value="<?php echo (is_array($options))?$options['cost']:QIXIT_DEFAUL_COST; ?>" />
      </td>
      
   </tr>
      <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Characters to show before &lsquo;More Link&rsquo;",'qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" name="qixit_settings[characters]"   value="<?php echo (is_array($options))?$options['characters']:QIXIT_DEFAUL_CHARACTERS; ?>" /> 
			 <small><?php echo __("This setting only invokes if your post doesn't have a more link.",'qixit');?></small>
      </td>
   </tr>
   
   
    <!--  Cookie Expiry time setting -->
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Pay once view anytime cookie expiry time",'qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" name="qixit_settings[view_any_time_cookie_exp]"   value="<?php echo (is_array($options))?($options['view_any_time_cookie_exp']/(60*60*24)):QIXIT_DEFAUL_COOKIE; ?>" /> 
          <small><?php echo __("This is number of days allowed for 'pay once view anytime'",'qixit');?></small>
      </td>
   </tr>
 <tr valign="top">
 <th scope="row" style="margin-left: 15px;"><?php echo __('Ad Hoc Links', 'qixit'); ?></th>
      <td >
  
          <?php echo __("Manually create your own purchase links to off site URL's or to any link on your own site(s) using the <a href='admin.php?page=qixit_ad_hoc_create'>ad hoc link creator</a>."); ?> 
      </td>
   </tr>

   
   <tr valign="top">
      
   	  
      <th scope="row" colspan="2" style="width: 100%">
          <strong><?php echo __('Premium Comments:', 'qixit'); ?></strong>
      </th>
   </tr>
   
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Enable Premium Comments",'qixit');?> :</th>
      <td>
         <input STYLE="background-color: #d0d0d0;border-color:black;" type="hidden" name="qixit_settings[paid_comments]" value="0"  />
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="checkbox" name="qixit_settings[paid_comments]" value="1" <?php echo ( is_array($options) && array_key_exists('paid_comments',$options))?(($options['paid_comments']==1)?"checked='checked'":''):"checked='checked'"; ?> />  
         <small> <a href="http://2waymicropay.com/plugin/wp-content/uploads/2010/11/comments.jpg" target='_blank'>Screen shot of premium/free comments using default theme</a></small>
      </td>
   </tr>  
    <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Enable Premium Comments Only",'qixit');?> :</th>
      <td>
         <input STYLE="background-color: #d0d0d0;border-color:black;" type="hidden" name="qixit_settings[paid_comments_only]" value="0"  />
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="checkbox" name="qixit_settings[paid_comments_only]" value="1" <?php echo ( is_array($options) && array_key_exists('paid_comments_only',$options))?(($options['paid_comments_only']==1)?"checked='checked'":''):" "; ?> />
         <small>This option disallows free comments. Use this option only on a new blog or only after closing comments for all prior posts. Otherwise, if this option is selected premium posts may not be properly sorted to appear above all of the old comments on old posts.</small>
      </td>
   </tr>    
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Premium Comments default price",'qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" name="qixit_settings[paid_comments_price]"   value="<?php echo (is_array($options))?$options['paid_comments_price']:QIXIT_DEFAUL_COST; ?>" /> 
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" name="qixit_settings_previous_paid_comments_price" value="<?php echo (is_array($options))?$options['paid_comments_price']:QIXIT_DEFAUL_COST; ?>" />
      </td>
   </tr>
  
      <!--  Premium Comment Heading BG color -->
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Premium comments heading background color",'qixit');?> :</th>
      <td>
         <input type="text" maxlength="6" size="6" name="qixit_settings[premium_heading_bg_color]" id="colorpickerField1" value="<?php echo (is_array($options))?($options['premium_heading_bg_color']):'B9CEE1'; ?>" readonly="readonly" style="float:left" />
            <div id="customWidget1" style="float:left" style="z-index:100" >
               <div id="colorSelector1"><div style="background-color: #<?php echo (is_array($options))?($options['premium_heading_bg_color']):'B9CEE1'; ?>"></div></div>
                   <div id="colorpickerHolder1" style="z-index:100"  ></div>
            </div>
            <script>
            //<![CDATA[ 
               jQuery(document).ready(function($) 
                              {   
                                 $('#colorpickerHolder1').ColorPicker({
                                    flat: true,
                                    color: '#<?php echo (is_array($options))?($options['premium_heading_bg_color']):'B9CEE1'; ?>',
                                    onSubmit: function(hsb, hex, rgb) {
                                       $('#colorSelector1 div').css('backgroundColor', '#' + hex);
                                       $('#colorpickerField1').val(hex);
                                    }
                                 });
                                 $('#colorpickerHolder1>div').css('position', 'absolute');
                                 var widt = false;
                                 $('#colorSelector1').bind('click', function() {
                                    $('#colorpickerHolder1').stop().animate({height: widt ? 0 : 173}, 500);
                                    widt = !widt;
                                 });
                                 
                              }
                        );
         //]]>
         </script>
      </td>
   </tr>
   
   <!--  Premium Comment BG color -->
   <tr valign="top">
      <th scope="row"><?php echo __("Premium comments background color",'qixit');?> :</th>
      <td>
         <input type="text" maxlength="6" size="6" name="qixit_settings[premium_bg_color]" id="colorpickerField2" value="<?php echo (is_array($options))?($options['premium_bg_color']):'D4E9FE'; ?>" readonly="readonly" style="float:left"/>
            <div id="customWidget2" style="float:left">
               <div id="colorSelector2"><div style="background-color: #<?php echo (is_array($options))?($options['premium_bg_color']):'D4E9FE'; ?>"></div></div>
                   <div id="colorpickerHolder2" ></div>
            </div>
         <script>
         //<![CDATA[ 
            jQuery(document).ready(function($) 
                              {   
                                 $('#colorpickerHolder2').ColorPicker({
                                    flat: true,
                                    color: '#<?php echo (is_array($options))?($options['premium_bg_color']):'D4E9FE'; ?>',
                                    onSubmit: function(hsb, hex, rgb) {
                                       $('#colorSelector2 div').css('backgroundColor', '#' + hex);
                                       $('#colorpickerField2').val(hex);
                                    }
                                 });
                                 $('#colorpickerHolder2>div').css('position', 'absolute');
                                 var widt = false;
                                 $('#colorSelector2').bind('click', function() {
                                    $('#colorpickerHolder2').stop().animate({height: widt ? 0 : 173}, 500);
                                    widt = !widt;
                                 });
                                 
                              }
                        );
         //]]>
         </script>
      </td>
   </tr>
   
   <!--  Guest Author Settings -->
   <tr valign="top">
      <th scope="row" colspan="2" style="width: 100%"><strong><?php echo __('Guest Author Settings','qixit');?> :</strong></th>
   </tr>
   
   
   	  <th scope="row" style="margin-left: 15px;"><?php echo __('Enable Guest Authors to Create Posts:', 'qixit'); ?></th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;" type="hidden" name="qixit_settings[e_guest_authors]" value="0"  />
          <input type="checkbox" name="qixit_settings[e_guest_authors]" value="1" <?php echo ( is_array($options) && array_key_exists('e_guest_authors',$options))?(($options['e_guest_authors']==1)?"checked='checked'":''):""; ?> onclick="toggle_visibility('hide_table')" /> 
      </td>
        
   
    <tr>
    	<td colspan="2">
        	<?php
            	if($options['e_guest_authors']==1){
					$style="display:block";	
				}else{
					$style="display:none";		
				}
			?>
        	<table  cellpadding="0" cellspacing="0" id="hide_table" style="<?php echo $style; ?>">
             <tr valign="top">
             <th scope="row" colspan="2" style="width: 100%">
                <?php echo __('When you enable the Guest Author option, we automatically create a page titled "Author Registration Help." You should edit this page to describe the terms and conditions for your guest authors.','qixit');?>
             </th>
             </tr>    		
                	
    <tr valign="top">
      <th scope="row" style="width: 68.7%; "><?php echo __('Readers may setup an author account for a price of','qixit');?> :</th>
      <td>
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" id="cost_to_be_author" name="qixit_settings[cost_to_be_author]" value="<?php echo (is_array($options))?$options['cost_to_be_author']:QIXIT_DEFAUL_COST; ?>" /> 
         <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" name="qixit_settings_previous_cost_to_be_author" value="<?php echo (is_array($options))?$options['cost_to_be_author']:QIXIT_DEFAUL_COST; ?>" />
      </td>
   </tr>   
   <tr valign="top">
      <th scope="row"><?php echo __('Authors may publish an article for a price of','qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" id="cost_to_publish_post_by_author"  name="qixit_settings[cost_to_publish_post_by_author]" value="<?php echo (is_array($options))?$options['cost_to_publish_post_by_author']:QIXIT_DEFAUL_COST; ?>" />
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" name="qixit_settings_previous_cost_to_publish_post_by_author" value="<?php echo (is_array($options))?$options['cost_to_publish_post_by_author']:QIXIT_DEFAUL_COST; ?>" />
      </td>
   </tr>
   <tr valign="top">
      <th scope="row" ><?php echo __('Enable premium content, with a default price of','qixit');?> :</th>
      <td>
          <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" id="post_cost_of_author" name="qixit_settings[post_cost_of_author]" value="<?php echo (is_array($options))?$options['post_cost_of_author']:QIXIT_DEFAUL_COST; ?>" />
      </td>
   </tr>
   <tr valign="top">
      <th scope="row" ><?php echo __("Author&rsquo;s percent share",'qixit');?> :</th>
      <td>
      <!-- <input STYLE="background-color: #d0d0d0;border-color:black;"type="text" size="10" name="qixit_settings[percent_to_author]" value="<?php echo (is_array($options))?$options['percent_to_author']:QIXIT_DEFAUL_PERCENT_TO_AUTHOR; ?>" />
      -->
      <input STYLE="background-color: #d0d0d0;border-color:black;"type="hidden" size="10" name="qixit_settings[percent_to_author]" value="0"/>
      
          <?php echo __('Payment split between blog and author, coming soon.', 'qixit'); ?>
      </td>
   </tr>

   
   
   </table>
   </td>
   </tr>
   &nbsp;</P>
    &nbsp;</P> &nbsp;</P>
   <tr valign="top">     
   	  
      <th scope="row" colspan="1" style="width: 100%">
          <strong><?php echo __('Terms of Service:', 'qixit'); ?></strong>
      </th>
   
   <td>
      <?php echo __('If you are collecting money, you should have some terms of service posted on your site.  The text below, for example, appears when a person attempts to post a premium comment.', 'qixit'); ?>
      </td>
      </tr>
   <!--  Terms of Service For Posting of Premium Comments setting -->
   <tr valign="top">
      <th scope="row" style="margin-left: 15px;"><?php echo __("Description for Terms of Service For Posting of Premium Comments",'qixit');?> :      
      </th>
      
      <td>
         <textarea STYLE="background-color: #d0d0d0;border-color:black;" name="qixit_settings[terms_of_service]" cols="50" rows="10" ><?php 
         if ( is_array($options) )
         {
            echo stripslashes($options['terms_of_service']); 
         }
         else
         {
            echo "<ol>
<li>Your non-refundable payment for posting a premium comment will result in immediate unedited posting of your comment in the section reserved for premium comments.</li>
<li>This website administrator and employees, however, reserve the right to edit or delete premium comments which violate our own personal standards of decency or which we may deem to be potentially libelous or otherwise problematic.</li>
</ol>";
         }
         ?></textarea>
      </td>
   </tr>
      <tr valign="top">
         <th scope="row"  colspan="2">
         <?php 
            echo "<center><a href='".QIXIT_PLUGIN_LINK."'  target='_blank' title='".__('Visit Plugin link','qixit')."'>".
                  __('Please do not forget to read the documentation at the plugin site.','qixit')."</a></center>"; 
         ?>
      </th>
   </tr>
   
<tr><th scope="row"><b>Give Us Some Love:</b></th>
	<td>Do you like this plugin? Then please <a target="_blank" href="http://wordpress.org/extend/plugins/2-way-micropay-monetize-3-ways/"> rate it 5 Stars</a> at the official Plugin Directory! <I> With your support and word of mouth, it will get better and better!</i><br/>
</td>
</tr>
<tr><th scope="row"><b></b></th>
	<td>If you really like it, <a target="_blank" href="http://best-reviewed.info/?p=190&adhoc">donate just 10 cents</a> using 2-Way Micropay!<br>
</td>
</tr>

   
</table>

<p class="submit"><input STYLE="background-color: #d0d0d0;border-color:black;"type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
</form>
</div>
<script>
	//alert();
</script>
<script type="text/javascript">
<!--
    function toggle_visibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block'){
          e.style.display = 'none';
		  document.getElementById('cost_to_be_author').value = '1000';
		  document.getElementById('cost_to_publish_post_by_author').value = '1000';
		  document.getElementById('post_cost_of_author').value = '1000';
	   }else{
          e.style.display = 'block';
		  document.getElementById('cost_to_be_author').value = '0.01';
		  document.getElementById('cost_to_publish_post_by_author').value = '0.01';
		  document.getElementById('post_cost_of_author').value = '0.01';
	   }
    }
//-->
</script>
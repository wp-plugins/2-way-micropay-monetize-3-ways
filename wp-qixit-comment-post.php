<?php
define('DOING_AJAX', false);
define('WP_ADMIN', false);
require_once('../../../wp-load.php');
$error='';
@session_start();
if ( !isset($_SESSION['QIXIT_POST_COMMENTS_DATA']) && isset($_GET['post_id']) )
{
   wp_redirect(get_permalink($_GET['post_id']).'#comments');
   die(" ");
}
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
wp_print_scripts('jquery');
wp_print_styles('qixit');
?>
<div class='qixit_wait_message'>Please wait...</div>
<form action="<?php echo get_option('siteurl').'/wp-comments-post.php';?>"    method="post" name="commentform">
   <input type="hidden" name="author"   value="<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['author'];?>" /> 
   <input type="hidden" name="email" value="<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['email'];?>" /> 
   <input type="hidden" name="url" value="<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['url'];?>" /> 
   <textarea name="comment" style="display: none"><?php echo stripslashes($_SESSION['QIXIT_POST_COMMENTS_DATA']['comment']);?></textarea>
   <input type='hidden' name='comment_post_ID' value='<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_post_ID'];?>' />
   <input type='hidden' name='comment_parent' value='<?php echo $_SESSION['QIXIT_POST_COMMENTS_DATA']['comment_parent'];?>' />
   <input type='hidden' name='qixit_comment_type' value='<?php echo QIXIT_PREMIUM;?>' /> 
   <input name="session_submit" type="submit" value="Submit Comment" style="display: none" /> 
   <script>
     if(typeof document.commentform.submit=="function") 
     {
        document.commentform.submit();
     }
     else if(typeof document.commentform.submit.click=="function") 
     {
         document.commentform.submit.click();
     } 
     else 
     {		 
         document.commentform.submit();
     }
   </script>
</form>
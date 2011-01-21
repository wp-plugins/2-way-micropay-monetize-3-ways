<?php
class QixitProduct
{
   var $product_id;
   var $post_id;
   var $post_qixit_PID;
   var $qixit_post_type;
   var $premium_post_cost;
   var $premium_post_url;
   var $comments_qixit_PID;
   var $premium_comments_cost;
   var $percent_to_author;
   var $has_post_product_changed;
   var $has_comments_product_changed;   
   
   function QixitProduct( $post_id = '' )
   {
      global $wpdb;
      if ( empty( $post_id ) )
      {
         $this->set_qixit_post_type(QIXIT_REG);
         $this->set_has_post_product_changed(false);
         $this->set_has_comments_product_changed(false);
         return;
      }
               

      $qixit_product_data = $wpdb->get_row("SELECT *  FROM " . $wpdb->prefix.QIXIT_PRODUCTS . " WHERE post_id='$post_id' ");
      if ( empty( $qixit_product_data ))
      {
         // looks like we still haven't saved the data in our database, so just construct a basic one and return
         $this->set_qixit_post_type(QIXIT_REG);
         $this->set_has_post_product_changed(false);
         $this->set_has_comments_product_changed(false);
         return;
      } 
      foreach ( get_object_vars( $qixit_product_data ) as $key => $value ) 
      {
         $this->{$key} = $value;
      }

      $this->set_has_post_product_changed(false);
      $this->set_has_comments_product_changed(false);
   }
   
   function get_product_id()
   {
      return $this->product_id;
   }

   function set_post_id($post_id)
   {
      $this->post_id = $post_id;
   }

   function get_post_id()
   {
      return $this->post_id;
   }
   
   function set_post_qixit_PID($post_qixit_PID)
   {
      $this->post_qixit_PID = $post_qixit_PID;
   }
   
   function get_post_qixit_PID()
   {
      return $this->post_qixit_PID;
   }
   
   function set_qixit_post_type($qixit_post_type)
   {
      $this->qixit_post_type = $qixit_post_type;
   }
   
   function get_qixit_post_type()
   {  
      return $this->qixit_post_type;
   }
   
   function set_premium_post_cost($premium_post_cost)
   {
      $this->premium_post_cost = $premium_post_cost;
   }
   
   function get_premium_post_cost()
   {
      return $this->premium_post_cost;
   }
   
   function set_percent_to_author($percent_to_author)
   {
      $this->percent_to_author = $percent_to_author;
   }
   
   function get_percent_to_author()
   {
      return $this->percent_to_author;
   }
   
   
   function set_premium_post_url($premium_post_url)
   {
      $this->premium_post_url = $premium_post_url;
   }
   
   function get_premium_post_url()
   {
      return $this->premium_post_url;      
   }
   
   function set_comments_qixit_PID($comments_qixit_PID)
   {
      $this->comments_qixit_PID = $comments_qixit_PID;
   }
   
   function get_comments_qixit_PID()
   {
      return $this->comments_qixit_PID;
   } 
   
   function set_premium_comments_cost($premium_comments_cost)
   {
      $this->premium_comments_cost = $premium_comments_cost;
   }
   
   function get_premium_comments_cost()
   {
      return $this->premium_comments_cost;
   }

   function set_has_post_product_changed($has_post_product_changed)
   {
      $this->has_post_product_changed = $has_post_product_changed;
   }
   
   function get_has_post_product_changed()
   {
      return $this->has_post_product_changed;
   }
      
   function set_has_comments_product_changed($has_comments_product_changed)
   {
      $this->has_comments_product_changed = $has_comments_product_changed;
   }
   
   function get_has_comments_product_changed()
   {
      return $this->has_comments_product_changed;
   }
}
?>
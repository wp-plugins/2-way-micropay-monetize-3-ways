<?php
class QixitAdHocProduct
{
   var $product_id;
   var $post_id;
   var $qixit_PID;
   var $cost;
   var $qixit_cost;
   var $qixit_pitch_url;
   var $qixit_delivery_url;
   var $qixit_ad_hoc_link_type;
   var $has_ad_hoc_product_changed;
   
   
   function QixitAdHocProduct( $post_id='' )
   {
      global $wpdb;
      if ( empty( $post_id ) )
      {
         $this->set_has_ad_hoc_product_changed(false);
         return;
      }
               
      $qixit_ad_hoc_product_data = $wpdb->get_row("SELECT *  FROM " . $wpdb->prefix.QIXIT_AD_HOC_PRODUCTS . " WHERE post_id='$post_id' ");
      if ( empty( $qixit_ad_hoc_product_data ))
      {
         // looks like we still haven't saved the data in our database, so just construct a basic one and return
         $this->set_has_ad_hoc_product_changed(false);
         return;
      } 
      foreach ( get_object_vars( $qixit_ad_hoc_product_data ) as $key => $value ) 
      {
         $this->{$key} = $value;
      }

      $_qixit_cost=get_post_meta( $post_id, '_qixit_cost' );
      $_qixit_pitch_url=get_post_meta( $post_id, '_qixit_pitch_url' );
      $_qixit_delivery_url=get_post_meta( $post_id, '_qixit_delivery_url' ); 
      $_qixit_ad_hoc_link_type=get_post_meta( $post_id, '_qixit_ad_hoc_link_type' ); 
      
      if ( empty($_qixit_cost) ) $_qixit_cost[]='';
      if ( empty($_qixit_pitch_url) ) $_qixit_pitch_url[]='';
      if ( empty($_qixit_delivery_url) ) $_qixit_delivery_url[]='';
      if ( empty($_qixit_ad_hoc_link_type) ) $_qixit_ad_hoc_link_type[]='';
      
      $this->set_qixit_cost( $_qixit_cost[0] );
      $this->set_qixit_pitch_url( $_qixit_pitch_url[0] );
      $this->set_qixit_delivery_url( $_qixit_delivery_url[0] );
      $this->set_qixit_ad_hoc_link_type( $_qixit_ad_hoc_link_type[0] );
         
      $this->set_has_ad_hoc_product_changed(false);
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
   
   function set_qixit_PID($qixit_PID)
   {
      $this->qixit_PID = $qixit_PID;
   }
   
   function get_qixit_PID()
   {
      return $this->qixit_PID;
   }
   
  
   function set_cost($cost)
   {
      $this->cost = $cost;
   }
   
   function get_cost()
   {
      return $this->cost;
   }
   
   
   function set_qixit_cost($qixit_cost)
   {
      $this->qixit_cost = $qixit_cost;
   }
   
   function get_qixit_cost()
   {
      return $this->qixit_cost;
   }


   function set_qixit_pitch_url($qixit_pitch_url)
   {
      $this->qixit_pitch_url = $qixit_pitch_url;
   }
   
   function get_qixit_pitch_url()
   {
      return $this->qixit_pitch_url;
   }

   function set_qixit_delivery_url($qixit_delivery_url)
   {
      $this->qixit_delivery_url = $qixit_delivery_url;
   }
   
   function get_qixit_delivery_url()
   {
      return $this->qixit_delivery_url;
   }
   
   function set_qixit_ad_hoc_link_type($qixit_ad_hoc_link_type)
   {
      $this->qixit_ad_hoc_link_type = $qixit_ad_hoc_link_type;
   }
   
   function get_qixit_ad_hoc_link_type()
   {
      return $this->qixit_ad_hoc_link_type;
   }
   
   function set_has_ad_hoc_product_changed($has_ad_hoc_product_changed)
   {
      $this->has_ad_hoc_product_changed = $has_ad_hoc_product_changed;
   }
   
   function get_has_ad_hoc_product_changed()
   {
      return $this->has_ad_hoc_product_changed;
   }

}
?>
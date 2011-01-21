<?php
class ProductAdd
{
   var $qixit_PID;
   var $vend;
   var $vendpw;
   var $desc;
   var $cost;
   var $aff;
   var $affpct;
   var $fwd;
   var $fwdpct;
   var $purl;
   var $durl;
   var $echo;
   var $perm;
   var $rmsg;
   var $siteurl;
   var $permalink;
   var $curl='y';
   
   function construct_product_url()
   {   
      $error_msg = $this->validate();
      if ($error_msg === true)
      {
         $url = "https://www.qixit.com/productadd.aspx";
         $url = $url . "?Vend=" . $this->vend;
         $url = $url . "&vendpw=" . $this->vendpw;
         if ($this->qixit_PID != '')
         {
            $url = $url . "&PID=".$this->qixit_PID;   
         }   
         $url = $url . "&Desc=" . $this->desc; 
         $url = $url . "&Cost=" . $this->cost; 
         $url = $url . "&aff=" . $this->aff;
         $url = $url . "&affpct=" . $this->affpct; 
         $url = $url . "&Purl=" . $this->purl;
         $url = $url . "&Durl=" . urlencode($this->durl.'&'.$this->echo);
         $url = $url . "&RMsg=" . $this->rmsg; 
         $url = $url . "&Perm=" . $this->perm;         
         $url = $url . "&curl=" . $this->curl;

         return $url;
      }
      else
      {
         return array('error_message'=>__($error_msg,'qixit'));
      }
   }

   function set_qixit_pid($pid)
   {
      $this->qixit_PID = $pid;
   }

   function get_qixit_pid()
   {
      return $this->qixit_PID;
   }

   function set_vend($vend)
   {
      $this->vend = $vend;
   }

   function get_vend()
   {
      return $this->vend;
   }

   function set_vendpw($vendpw)
   {
      $this->vendpw = $vendpw;
   }

   function get_vendpw()
   {
      return $this->vendpw;
   }

   function set_perm($perm)
   {
      $this->perm = $perm;
   }

   function get_perm()
   {
      return $this->perm;
   }

   function set_siteurl($siteurl)
   {
      $this->siteurl = urlencode($siteurl);
   }

   function get_siteurl()
   {
      return $this->siteurl;
   }

   function set_rmsg($rmsg)
   {
      $this->rmsg = str_replace(" ", "+", $rmsg);   
   }
   function get_rmsg()
   {
      return $this->rmsg;
   }

   function set_permalink($permalink)
   {
      $this->permalink = urlencode($permalink);
   }

   function get_permalink()
   {
      return $this->permalink;
   }

   function set_desc($desc)
   {
      $this->desc = str_replace(" ", "+", $desc);
   }

   function get_desc()
   {
      return $this->desc;
   }

   function set_cost($cost)
   {
      $this->cost = $cost;
   }

   function get_cost()
   {
      return $this->cost;
   }

   function set_aff($aff)
   {
      //$this->aff = $aff;
      // change to blank, till the time we get a fix from Qixit
      $this->aff = '';
   }

   function get_aff()
   {
      return $this->aff;
   }

   function set_affpct($affpct)
   {
      $this->affpct = $affpct;
   }

   function get_affpct()
   {
      return $this->affpct;
   }

   function set_fwd($fwd)
   {
      $this->fwd = $fwd;
   }

   function get_fwd()
   {
      return $this->fwd;
   }

   function set_fwdpct($fwdpct)
   {
      $this->fwdpct = $fwdpct;
   }

   function get_fwdpct()
   {
      return $this->fwdpct;
   }

   function set_purl($purl)
   {
      $this->purl = urlencode($purl);
   }

   function get_purl()
   {
      return $this->purl;
   }

   function set_durl($durl)
   {
      //$this->durl = urlencode($durl);
      $this->durl = $durl;
   }

   function get_durl()
   {
      //return $this->durl;
      return urlencode($this->durl.'&'.$this->echo);
   }

   function set_echo($echo)
   {
      $this->echo = 'echo='.$echo;
   }

   function get_echo()
   {
      return $this->echo;
   }

   function get_curl()
   {
      return $this->curl;
   }

   function validate()
   {
      $error = false;
      $error_msg = '';

      if ( trim($this->vend) == '' )
      {
         $error = true;
         $error_msg .= "Vendor Qixit ID cannot be blank.";
      }

      if ( trim($this->vendpw) == '' )
      {
         $error=true;
         $error_msg .= "Vendor Qixit Password cannot be blank.";
      }

      if ( trim($this->desc) == '' )
      {
         $error=true;
         $error_msg .= "Description cannot be blank.";
      }

      if ( trim($this->siteurl) == '' )
      {
         $error=true;
         $error_msg .= "Site url cannot be blank.";
      }

      if ( trim($this->perm) == '' )
      {
         $error=true;
         $error_msg .= "perm cannot be blank.";
      }

      if ( trim($this->purl) == '' )
      {
         $error=true;
         $error_msg .= "Pitch URL cannot be blank.";
      }

      if ( trim($this->durl) == '' )
      {
         $error=true;
         $error_msg .= "Delivery URL cannot be blank.";
      }

      if ( trim($this->curl) == '' )
      {
         $error=true;
         $error_msg .= "curl value cannot be blank.";
      }

      if ( trim($this->cost)=='' )
      {
         $error=true;
         $error_msg .= "Cost cannot be blank.";
      }

      if ($error===true)
      {
         return $error_msg;
      }
      else
      {
         return true;
      }
   }
}
?>
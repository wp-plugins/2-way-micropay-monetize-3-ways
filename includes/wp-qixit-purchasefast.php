<?php
class PurchaseFast
{
   var $qixit_PID; 
   
   function qixit_construct_url()
   {
      if ($this->validate()===true)
      {
          return "https://www.qixit.com/purchasefast.aspx?PID=".$this->qixit_PID;
      }
      else
      {
          return QIXIT_PLUGIN_URL."/wp-qixit-error.php?message=Qixit PID not found";
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

   function validate()
   {
     $error=false;
     $error_msg='';
   
     if (trim($this->qixit_PID)=='')
     {
       $error=true;
       $error_msg="Qixit PID not found.";
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
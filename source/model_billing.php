<?php

class lib_billing { 
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    } 
    
    function get_discounts() {
        $STH = $this->DBH->prepare("SELECT * FROM `billing_discounts` WHERE active = '1'");       
        $STH->execute();
        return $STH->fetchAll();     
    }      
    

    function log($presc_id, $entry_date, $entry_time, $updated_by, $log_status, $remarks) {  
        $STH = $this->DBH->prepare("INSERT INTO `presc_logs` VALUES (
            :presc_id,                     
            :entry_date,                   
            :entry_time,                   
            :updated_by,                   
            :log_status,                   
            :remarks 
            )");        
        $STH->bindParam(':presc_id', $presc_id );                             
        $STH->bindParam(':entry_date', $entry_date );                         
        $STH->bindParam(':entry_time', $entry_time );                         
        $STH->bindParam(':updated_by', $updated_by );                         
        $STH->bindParam(':log_status', $log_status );                         
        $STH->bindParam(':remarks', $remarks ); 
        $STH->execute();          
        
    }    
  
    
   
     
}

?>
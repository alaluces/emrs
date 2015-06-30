<?php

class lib_prescriptions { 
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    } 
    
    function get_list($person_id) {       
        $STH = $this->DBH->prepare("SELECT presc_id, person_id, physician_id,
            (SELECT DATE_FORMAT(CONCAT(SUBSTR(entry_date,1,4),'-',SUBSTR(entry_date,5,2),'-',SUBSTR(entry_date,7,2)),'%M %d, %Y') 
            FROM presc_logs 
            WHERE presc_id = pl.presc_id
            ORDER BY entry_date LIMIT 1  ) AS created,
            (SELECT DATE_FORMAT(CONCAT(SUBSTR(entry_date,1,4),'-',SUBSTR(entry_date,5,2),'-',SUBSTR(entry_date,7,2)),'%M %d, %Y') 
            FROM presc_logs 
            WHERE presc_id = pl.presc_id
            ORDER BY entry_date DESC LIMIT 1  ) AS edited,
            (SELECT log_status
            FROM presc_logs 
            WHERE presc_id = pl.presc_id
            ORDER BY entry_date DESC, entry_time DESC LIMIT 1  ) AS log_status  
            FROM `presc_list` AS pl
            WHERE person_id = :person_id");      
        $STH->bindParam(':person_id', $person_id); 
        $STH->execute();
        return $STH->fetchAll();       
    }      
    
    function exists($presc_id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `presc_list` WHERE presc_id = :presc_id");
        $STH->bindParam(':presc_id', $presc_id);           
        $STH->execute();
        return $STH->fetchColumn();
    }  
    
    function get_new_id() {
        $STH = $this->DBH->prepare("SELECT MAX(presc_id) + 1 FROM `presc_list`");       
        $STH->execute();
        $pid = $STH->fetchColumn();
        if ($pid == '' || $pid == '0') {
            return '1';
        } else {
            return $pid;
        }       
    }    

    function save($presc_id, $person_id, $physician_id) { 
          
        if ($presc_id == '' || $person_id == '') {
            return 0;            
        }   
        
        if (!$this->exists($presc_id)) {            
            $STH = $this->DBH->prepare("INSERT INTO presc_list VALUES (
                :presc_id,                     
                :person_id,                    
                :physician_id   
                )");
        } else {              
            $STH = $this->DBH->prepare("UPDATE presc_list SET                                          
                person_id = :person_id,                        
                physician_id = :physician_id
                WHERE presc_id = :presc_id
                ");
        }  

        $STH->bindParam(':presc_id', $presc_id );                             
        $STH->bindParam(':person_id', $person_id );                           
        $STH->bindParam(':physician_id', $physician_id );             
        $STH->execute();  
        return 1 ; 
        
    }
    
    // will be used for looping
    function add_entry($presc_id, $med_id, $prep_id, $qty, $amt, $frequency, $duration, $notes) {        
        $STH = $this->DBH->prepare("INSERT INTO presc_entries VALUES (
            :presc_id,                     
            :med_id,                       
            :prep_id,                      
            :qty,                          
            :amt,                          
            :frequency,                    
            :duration,                     
            :notes               
            )");      

        $STH->bindParam(':presc_id', $presc_id );                             
        $STH->bindParam(':med_id', $med_id );                                 
        $STH->bindParam(':prep_id', $prep_id );                               
        $STH->bindParam(':qty', $qty );                                       
        $STH->bindParam(':amt', $amt );                                       
        $STH->bindParam(':frequency', $frequency );                           
        $STH->bindParam(':duration', $duration );                             
        $STH->bindParam(':notes', $notes );     
        return $STH->execute();          
    }  
    
    function delete_entries($presc_id) {       
        $STH = $this->DBH->prepare("DELETE FROM presc_entries where presc_id = :presc_id");
        $STH->bindParam(':presc_id', $presc_id);                                     
        return $STH->execute();                
    }  
    
    function get_entries($presc_id) {       
        $STH = $this->DBH->prepare("SELECT 
            pe.*,ml.med_name,mp.prep_name
            FROM `presc_entries` AS pe
            INNER JOIN `meds_list` AS ml
            ON pe.med_id = ml.med_id
            left JOIN `meds_prep` AS mp
            ON pe.prep_id = mp.prep_id
            WHERE presc_id = :presc_id");      
        $STH->bindParam(':presc_id', $presc_id); 
        $STH->execute();
        return $STH->fetchAll();       
    }     
    
    /*
    function has_duplicate_entry($presc_id, $med_id, $prep_id) {       
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `presc_entries` 
            WHERE presc_id = :presc_id
            AND med_id = :med_id
            AND prep_id = :prep_id");      
        $STH->bindParam(':presc_id', $presc_id); 
        $STH->bindParam(':med_id', $med_id);
        $STH->bindParam(':prep_id', $prep_id);
        $STH->execute();
        return $STH->fetchColumn();       
    }    
    */
    
    // will be used in place of has_duplicate_entry()
    // because some meds doesnt have preparations / brand
    // 20150429
    function has_duplicate_meds($presc_id, $med_id) {       
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `presc_entries` 
            WHERE presc_id = :presc_id
            AND med_id = :med_id");      
        $STH->bindParam(':presc_id', $presc_id); 
        $STH->bindParam(':med_id', $med_id);
        $STH->execute();
        return $STH->fetchColumn();       
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
<?php

class lib_medications { 
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    } 
    
    function find($med_name) {
        $med_name = $med_name . '%'; 
        $STH = $this->DBH->prepare("SELECT * FROM `meds_list` WHERE med_name LIKE :med_name");            
        $STH->bindParam(':med_name', $med_name);
        $STH->execute();
        return $STH->fetchAll();        
    }    

    // used by prescriptions to list meds and dosages/preps
    function get_meds() {
        $STH = $this->DBH->prepare("SELECT ml.med_id, prep_id, med_name, prep_name FROM `meds_list` AS ml
            INNER JOIN `meds_prep` AS mp
            ON ml.med_id = mp.med_id
            WHERE ml.active = '1'
            AND mp.active = '1'
            ORDER BY med_name");        
        $STH->execute();
        return $STH->fetchAll();
   
    }   
    
    // used by tools/meds
    function list_meds($active_only) {        
        if ($active_only) { $q = " WHERE active = '1' ORDER BY med_name"; } else { $q = ' ORDER BY med_name'; } 
        $STH = $this->DBH->prepare("SELECT * FROM `meds_list`" . $q);    
        $STH->execute();
        return $STH->fetchAll();       
    } 
    
    function get_details($id) {
        $STH = $this->DBH->prepare("SELECT * FROM `meds_list` WHERE med_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return  $STH->fetch();
    } 
    
    // used by search funtion 20150422
    function get_preps() {
        $STH = $this->DBH->prepare("SELECT * FROM `meds_prep`");       
        $STH->execute();
        return  $STH->fetchAll();
    }    
    
    function get_prep($id) {
        $STH = $this->DBH->prepare("SELECT * FROM `meds_prep` WHERE med_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return  $STH->fetchAll();
    } 
    
    function get_new_med_id() {
        $STH = $this->DBH->prepare("SELECT MAX(med_id) + 1 FROM `meds_list`");        
        $STH->execute();
        $mid = $STH->fetchColumn();
        if ($mid == '') {
            return '1';
        } else {
            return $mid;
        }     
    } 
    
    function get_new_prep_id() {
        $STH = $this->DBH->prepare("SELECT MAX(prep_id) + 1 FROM `meds_prep`");        
        $STH->execute();
        $pid = $STH->fetchColumn();
        if ($pid == '') {
            return '1';
        } else {
            return $pid;
        }     
    }    
    
    function exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM meds_list WHERE med_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }  
    
    function prep_exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM meds_prep WHERE prep_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }       
    
    function save($med_id, $med_name, $active) {
        if ($med_id == '') {
            return 0;            
        }           
        if (!$this->exists($med_id)) {           
            $STH = $this->DBH->prepare("INSERT INTO meds_list VALUES (
                :med_id,                       
                :med_name,                     
                :active              
                )");
        } else {            
            $STH = $this->DBH->prepare("UPDATE meds_list SET                             
                med_name = :med_name,                          
                active = :active    
                WHERE med_id = :med_id 
                ");
        } 

        $STH->bindParam(':med_id', $med_id );                                 
        $STH->bindParam(':med_name', $med_name );                             
        $STH->bindParam(':active', $active );                 
        return $STH->execute();                
    }     
    
    function prep_save($prep_id, $med_id, $prep_name, $active) {
        if ($prep_id == '') {
            return 0;            
        }           
        if (!$this->prep_exists($prep_id)) {           
            $STH = $this->DBH->prepare("INSERT INTO meds_prep VALUES (
                :prep_id,                      
                :med_id,                       
                :prep_name,                    
                :active                
                )");
        } else {            
            $STH = $this->DBH->prepare("UPDATE meds_prep SET                           
                med_id = :med_id,                              
                prep_name = :prep_name,                        
                active = :active   
                WHERE prep_id = :prep_id
                ");
        } 

        $STH->bindParam(':prep_id', $prep_id );                               
        $STH->bindParam(':med_id', $med_id );                                 
        $STH->bindParam(':prep_name', $prep_name );                           
        $STH->bindParam(':active', $active );                 
        return $STH->execute();                
    }     
   
    
   
     
}

?>
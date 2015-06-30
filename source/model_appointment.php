<?php

class lib_appointment {
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    }
    
    // 20150216 modified for hepa b / c appointments
    function get_list($entry_date, $set_number) {        
        $STH = $this->DBH->prepare("SELECT 
            a.appointment_id,
            a.person_id,
            CONCAT(fname,' ',lname) AS name,
            ( SELECT appointment_status FROM `appointment_history`
            WHERE appointment_status = 'RESERVED' 
            AND appointment_id = a.appointment_id limit 1) AS is_reserved,             
            (SELECT MAX(entry_time) FROM 
            `appointment_history` WHERE appointment_id = a.appointment_id
            AND appointment_status = 'WAITING') AS time_added,                
            (SELECT set_number FROM 
            `appointment_history` WHERE appointment_id = a.appointment_id 
            ORDER BY entry_time DESC LIMIT 1) AS set_numberx,
            (SELECT appointment_status FROM `appointment_history` WHERE appointment_id = a.appointment_id
            ORDER BY entry_time DESC LIMIT 1) AS appointment_status,
            pd.hepa_status AS hepatitis_status
            FROM `appointment_history` AS h
            INNER JOIN appointments AS a
            ON a.appointment_id = h.appointment_id
            INNER JOIN persons AS p
            ON a.person_id = p.person_id
            INNER JOIN patient_details AS pd
            ON pd.person_id = a.person_id 
            WHERE entry_date = :entry_date 
            GROUP BY a.appointment_id
            HAVING set_numberx = :set_number
            ORDER BY time_added
            ");
        
        $STH->bindParam(':entry_date', $entry_date); 
        //$set_number = '%' . $set_number . '%';        
        $STH->bindParam(':set_number', $set_number);  
        //$STH->bindParam(':hepatitis_status', $hepatitis_status);
        $STH->execute();
        return $STH->fetchAll();       
    }  
    
    function exists($id, $entry_date) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `appointments` AS a
                INNER JOIN `appointment_history` AS h
                ON a.appointment_id = h.appointment_id
                WHERE person_id = :id
                AND entry_date = :entry_date");        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':id', $id );
        $STH->execute();
        return  $STH->fetchColumn();         
    } 
    
    function history_exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `appointment_history`                
                WHERE appointment_id = :id");   
        $STH->bindParam(':id', $id );
        $STH->execute();
        return  $STH->fetchColumn();         
    } 
    
    function get_history($id) {  
        $STH = $this->DBH->prepare("SELECT appointment_id AS aid,
            (SELECT entry_date FROM `appointment_history` WHERE appointment_id = aid ORDER BY entry_date,entry_time DESC LIMIT 1) AS appointment_date,
            (SELECT DATE_FORMAT(CONCAT(SUBSTR(entry_date,1,4),'-',SUBSTR(entry_date,5,2),'-',SUBSTR(entry_date,7,2)),'%M %d, %Y') 
               FROM `appointment_history` WHERE appointment_id = aid ORDER BY entry_date,entry_time DESC LIMIT 1) AS appointment_date_readable,
            (SELECT entry_time FROM `appointment_history` WHERE appointment_id = aid ORDER BY entry_date,entry_time DESC LIMIT 1) AS appointment_time,
            (SELECT set_number FROM `appointment_history` WHERE appointment_id = aid ORDER BY entry_date,entry_time DESC LIMIT 1)  AS set_number,
            (SELECT appointment_status FROM `appointment_history` WHERE appointment_id = aid ORDER BY entry_date,entry_time DESC LIMIT 1) AS appointment_status
            FROM `appointments`
            WHERE person_id = :id");
     
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetchAll();       
    }    
    
    function add($aid, $id) {
        if ($aid == '') { $aid = $this->get_new_id(); }        
        $STH = $this->DBH->prepare("INSERT INTO `appointments` VALUES (
                '$aid', :id
                )");    
        //$STH->bindParam(':set_number',$set_number);  
        $STH->bindParam(':id', $id);                      
        $STH->execute();       
    } 
    
    function get_count($entry_date, $hepatitis_status, $set_number) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM 
                (SELECT 
                    (SELECT set_number FROM 
                    `appointment_history` WHERE appointment_id = a.appointment_id 
                    ORDER BY entry_date, entry_time DESC LIMIT 1) AS set_numberx,
                    (SELECT appointment_status FROM `appointment_history` WHERE appointment_id = a.appointment_id
                    ORDER BY entry_date, entry_time DESC LIMIT 1) AS appointment_status,               
                    pd.hepa_status AS hepatitis_status
                    FROM `appointment_history` AS h
                    INNER JOIN appointments AS a
                    ON a.appointment_id = h.appointment_id
                    INNER JOIN persons AS p                  
                    ON a.person_id = p.person_id
		    INNER JOIN patient_details AS pd
		    ON pd.person_id = a.person_id                        
                    WHERE entry_date = :entry_date
                    GROUP BY a.appointment_id
                    HAVING set_numberx = :set_number 
                    AND hepatitis_status = :hepatitis_status
                    AND appointment_status NOT IN ('CANCELLED','REMOVED')                   
                ) AS t
            ");        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':set_number', $set_number); 
        $STH->bindParam(':hepatitis_status', $hepatitis_status );
        $STH->execute(); 
        return $STH->fetchColumn();       
    }     
 
    function get_id($id, $entry_date) {
        $STH = $this->DBH->prepare("SELECT a.appointment_id 
            FROM `appointment_history` AS h
            INNER JOIN appointments AS a
            ON h.appointment_id = a.appointment_id 
            WHERE entry_date = :entry_date
            AND person_id = :id
            LIMIT 1
            ");        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':id', $id );
        $STH->execute(); 
        return  $STH->fetchColumn();       
    }       
    
    function get_new_id() {
        $STH = $this->DBH->prepare("SELECT MAX(appointment_id) + 1 FROM `appointments`");          
        $STH->execute();
        $aid = $STH->fetchColumn(); 
        if ($aid == '') {
            return '1';
        } else {
            return $aid;
        }        
    }           
    
    function update($aid, $entry_date, $entry_time, $set_number, $appointment_status ) {
        // 20150309 fixes a bug where an appointment disappears when 
        // an appointmentent is set again after it is removed
        if ( $entry_date != date("Ymd") ) {
            // BUG - if nag pa reserve ng later time, tapos waiting ng earlier than reserved, 
            // reserved pa rin status because of time stamp 
            // solved - just put 000000 in reserved time            
            $entry_time = '000000';
            if (!$this->history_exists($aid)) {
            $STH = $this->DBH->prepare("INSERT INTO `appointment_history` 
                VALUES (:appointment_id, :entry_date, :entry_time, :set_number, :appointment_status)");                
            } else {
            $STH = $this->DBH->prepare("update `appointment_history` set 
                entry_date = :entry_date,                      
                entry_time = :entry_time,                      
                set_number = :set_number,                      
                appointment_status = :appointment_status
                WHERE appointment_id = :appointment_id
                ");                               
            }
        } else {
            $STH = $this->DBH->prepare("INSERT INTO `appointment_history` 
                VALUES (:appointment_id, :entry_date, :entry_time, :set_number, :appointment_status)");             
        }     
        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':entry_time', $entry_time);
        $STH->bindParam(':appointment_id', $aid); 
        $STH->bindParam(':set_number', $set_number); 
        $STH->bindParam(':appointment_status', $appointment_status);                  
        $STH->execute();   
        
    }    
    
    function get_status($aid, $entry_date) {
        $STH = $this->DBH->prepare("SELECT appointment_status FROM `appointment_history` 
            WHERE appointment_id = :aid
            AND entry_date = :entry_date 
            ORDER BY entry_date,entry_time DESC 
            LIMIT 1");        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':aid', $aid );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }   
    
    function get_status_time($id, $entry_date, $appointment_status) {
        $STH = $this->DBH->prepare("SELECT MAX(entry_time) FROM 
            `appointment_history` AS h
            INNER JOIN appointments AS a
            ON h.appointment_id = a.appointment_id
            WHERE appointment_status = :appointment_status
            AND entry_date = :entry_date
            AND person_id = :id");    
        $STH->bindParam(':appointment_status', $appointment_status); 
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':id', $id );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }      
    
    function get_set_number($aid, $entry_date) {
        $STH = $this->DBH->prepare("SELECT set_number FROM `appointment_history` 
            WHERE appointment_id = :aid
            AND entry_date = :entry_date 
            ORDER BY entry_date, entry_time DESC 
            LIMIT 1");        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':aid', $aid );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }     
    
}

?>
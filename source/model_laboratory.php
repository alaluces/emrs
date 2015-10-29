<?php

class lib_laboratory { 
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    } 
    
    function get_new_eid() {
        $STH = $this->DBH->prepare("SELECT MAX(entry_id) + 1 FROM `lab_entry_list`");        
        $STH->execute();
        $eid = $STH->fetchColumn();
        if (!$eid) {
            return '1';
        } else {
            return $eid;
        }     
    }
    
    // aka $this->exists
    function get_eid($id, $cid, $entry_date) {
        $STH = $this->DBH->prepare("SELECT lel.entry_id FROM `lab_entry_list` AS lel
            INNER JOIN `lab_logs` AS ll
            ON lel.entry_id = ll.entry_id
            WHERE person_id = :id
            AND category_id = :cid
            AND entry_date = :entry_date");
        $STH->bindParam(':id', $id); 
        $STH->bindParam(':cid', $cid); 
        $STH->bindParam(':entry_date', $entry_date);         
        $STH->execute();
        return $STH->fetchColumn();
    }  
    
    function new_entry($entry_id, $person_id, $category_id) {       
        $STH = $this->DBH->prepare("INSERT INTO `lab_entry_list` VALUES (
            :entry_id,                     
            :person_id,                    
            :category_id
            )");    
        $STH->bindParam(':entry_id', $entry_id );                             
        $STH->bindParam(':person_id', $person_id );                           
        $STH->bindParam(':category_id', $category_id );                     
        $STH->execute();        
    }      
    
    function save($post_array, $eid) {
        // Loop thru each post variables
        foreach($post_array as $key => $val) {            
            $arrtmp = explode(',', $key);
            if (count($arrtmp) <= 1) {
                continue;
            }
            $this->add_entries($eid, $arrtmp[0], $val);             
        }       
    }
    
    // this is used by $this->save() for looping / multiple entries
    function add_entries($entry_id, $property_id, $entry_value) {  
        if ($entry_value == '') { $entry_value = ' '; }        
        $STH = $this->DBH->prepare("INSERT INTO `lab_entries` VALUES (
            :entry_id,                     
            :property_id,                  
            :entry_value     
            )");    
        $STH->bindParam(':entry_id', $entry_id );                             
        $STH->bindParam(':property_id', $property_id );                       
        $STH->bindParam(':entry_value', $entry_value );                    
        $STH->execute();        
    } 
    
    function delete_entries($eid) {  
        $STH = $this->DBH->prepare("DELETE FROM `lab_entries` WHERE entry_id = :eid");    
        $STH->bindParam(':eid', $eid); 
        $STH->execute();        
    }   
    
    function delete_logs($eid) {  
        $STH = $this->DBH->prepare("DELETE FROM `lab_logs` WHERE entry_id = :eid");      
        $STH->bindParam(':eid', $eid); 
        $STH->execute();        
    }   
    
    // logging function saved on entry_logs table    
    function log($entry_id, $entry_date, $entry_time, $updated_by, $log_status, $remarks) {  
        $STH = $this->DBH->prepare("INSERT INTO `lab_logs` VALUES (
            :entry_id,                     
            :entry_date,                   
            :entry_time,                   
            :updated_by,                   
            :log_status,                   
            :remarks   
            )");        
        $STH->bindParam(':entry_id', $entry_id );                             
        $STH->bindParam(':entry_date', $entry_date );                         
        $STH->bindParam(':entry_time', $entry_time );                         
        $STH->bindParam(':updated_by', $updated_by );                         
        $STH->bindParam(':log_status', $log_status );                         
        $STH->bindParam(':remarks', $remarks ); 
        $STH->execute();          
        
    }

    // to be deleted
    function get_all_results_old($id) {  
        $STH = $this->DBH->prepare("SELECT lp.property_id,
            lp.category_id,
            category_name,
            property_name,
            normal_value,
            (SELECT GROUP_CONCAT(entry_value ORDER BY ll.entry_date, ll.entry_date) 
            FROM `lab_entries` AS le
            INNER JOIN `lab_entry_list` AS lel
            ON le.entry_id = lel.entry_id 
            INNER JOIN `lab_logs` AS ll
            ON le.entry_id = ll.entry_id             
            WHERE person_id = :id
            AND property_id = lp.property_id
            GROUP BY property_id) AS val
            FROM `lab_properties` AS lp
            INNER JOIN `lab_categories` AS lc
            ON lp.category_id = lc.category_id
            where lp.active = '1'"); 
        $STH->bindParam(':id', $id);
        $STH->execute();
        return $STH->fetchAll();       
    }    
    
    function get_all_results($id) {  
        $STH = $this->DBH->prepare("SELECT 
            ll.entry_id,
            category_id,
            entry_date,
            (SELECT GROUP_CONCAT(concat(entry_value,'|',property_id) ORDER BY property_id) FROM lab_entries AS le
            WHERE entry_id = ll.entry_id
            ORDER BY property_id) AS entry_value
            FROM lab_logs AS ll
            INNER JOIN `lab_entry_list` AS lel
            ON ll.entry_id = lel.entry_id
            WHERE person_id = :id
            ORDER BY entry_date"); 
        $STH->bindParam(':id', $id);
        $STH->execute();
        return $STH->fetchAll();       
    }       
   
    function get_category_list($pid, $cid) { 
        if ($cid == 'all') { $c = ''; } else { $c = "AND category_id = :cid"; }
        $STH = $this->DBH->prepare("SELECT category_id, category_name,
            (SELECT GROUP_CONCAT(entry_date ORDER BY entry_date) 
            FROM `lab_logs` AS ll
            INNER JOIN `lab_entry_list` AS lel
            ON ll.entry_id = lel.entry_id  
            WHERE person_id = :pid
            AND lel.category_id = lc.category_id
            ) AS date_headers   
            FROM `lab_categories` AS lc            
            WHERE active = '1'
            $c
            ");   
        $STH->bindParam(':pid', $pid);
        if ($cid != 'all') { $STH->bindParam(':cid', $cid); } 
        $STH->execute();
        return $STH->fetchAll();       
    } 
    

    function get_category_menu_list() { 
        $STH = $this->DBH->prepare("SELECT * from lab_categories WHERE active = '1' ORDER BY category_id");                
        $STH->execute();
        return $STH->fetchAll();       
    }    
    
    function get_delete_list($id) {  
        $STH = $this->DBH->prepare("SELECT concat(property_id,',X') AS id FROM `lab_properties` WHERE category_id != :id");  
        $STH->bindParam(':id', $id);
        $STH->execute();
        return $STH->fetchAll();    
    } 
    
    function get_property_list() { 
        $STH = $this->DBH->prepare("SELECT property_id, category_name, property_name, lp.category_id, normal_value FROM `lab_properties` AS lp
            INNER JOIN `lab_categories` AS lc
            ON lp.category_id = lc.category_id
            WHERE lp.active = '1' ORDER BY category_name, property_id");                
        $STH->execute();
        return $STH->fetchAll();       
    } 
    
    function get_report_headers($pid, $cid) {
        // yes, this is possible 20150509
        //if ($pid == 'all') { $p = ''; } else { $p = "AND lel.person_id = :pid"; }
        //if ($cid == 'all') { $c = ''; } else { $c = "AND lel.category_id = :cid"; }
        $STH = $this->DBH->prepare("	SELECT 
	CONCAT(fname,' ', lname ) as pname
	FROM `lab_entries` AS le
	INNER JOIN `lab_entry_list` AS lel
	ON le.entry_id = lel.entry_id
	INNER JOIN persons AS p
	ON lel.person_id = p.person_id 
	WHERE lel.person_id IN ($pid)
	AND property_id IN ($cid)
	GROUP BY p.person_id"); 
        //$STH->bindParam(':pid', $pid);
        //$STH->bindParam(':cid', $cid);  
        $STH->execute();
        return $STH->fetchAll();       
    }     
    
    function get_report_values($pid, $cid) {
        // yes, this is possible 20150509
        //if ($pid == 'all') { $p = ''; } else { $p = "AND person_id = :pid"; }
        //if ($cid == 'all') { $c = ''; } else { $c = "AND lel.category_id = :cid"; }
        $STH = $this->DBH->prepare("SELECT property_name, normal_value, GROUP_CONCAT(latest_entry ORDER BY person_id) as entries
            FROM
            (
            SELECT 
            person_id,
            property_id,
            SUBSTRING_INDEX(GROUP_CONCAT(entry_value ORDER BY entry_date DESC), ',', 1) AS latest_entry 
            FROM `lab_entries` AS le
            INNER JOIN `lab_entry_list` AS lel
            ON le.entry_id = lel.entry_id
            INNER JOIN `lab_logs` AS ll
            ON le.entry_id = ll.entry_id
            WHERE lel.person_id IN ($pid)
            AND property_id IN ($cid)
            GROUP BY person_id ,property_id
            ORDER BY person_id,property_id, entry_date DESC
            ) AS c
            INNER JOIN lab_properties AS lp 
            ON c.property_id = lp.property_id
            INNER JOIN lab_categories AS lc
            ON lp.category_id = lc.category_id
            GROUP BY c.property_id"); 
        //if ($pid != 'all') { $STH->bindParam(':pid', $pid); }
        //if ($cid != 'all') { $STH->bindParam(':cid', $cid); }     
        $STH->execute();
        return $STH->fetchAll();       
    }
    
    function get_latest_data($pid, $prop_id) {
        $STH = $this->DBH->prepare("SELECT entry_value, entry_date 
            FROM `lab_entries` AS le
            INNER JOIN `lab_entry_list` AS lel
            ON le.entry_id = lel.entry_id
            INNER JOIN `lab_logs` AS ll
            ON le.entry_id = ll.entry_id
            WHERE lel.person_id = :pid
            AND le.property_id = :prop_id
            ORDER BY entry_date DESC, entry_time DESC
            LIMIT 1");
        $STH->bindParam(':pid', $pid); 
        $STH->bindParam(':prop_id', $prop_id); 
         
        $STH->execute();
        return $STH->fetch();
    }    
    
     
}

?>
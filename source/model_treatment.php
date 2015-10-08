<?php

class lib_treatment { 
    function __construct($DBH) {        
        $this->DBH = $DBH;
    }
    
    //======================================================================
    // Below are functions used for viewing the treatment form    
    
    function exists($id, $entry_date) {
        $STH = $this->DBH->prepare("SELECT tl.treatment_id FROM treatment_list AS tl
                INNER JOIN treatment_logs AS tg
                ON tl.treatment_id = tg.treatment_id
                WHERE person_id = :id
                AND entry_date = :entry_date
                ");
        $STH->bindParam(':id', $id); 
        $STH->bindParam(':entry_date', $entry_date);       
        $STH->execute();
        return $STH->fetchColumn();
    }  
    
    function id_exists($id) {
        $STH = $this->DBH->prepare("SELECT treatment_id FROM treatment_list AS tl               
                WHERE treatment_id = :id
                ");
        $STH->bindParam(':id', $id);        
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function get_vid($tid) {
        $STH = $this->DBH->prepare("SELECT MAX(version_id) FROM `treatment_logs` AS lo
                INNER JOIN `treatment_list` AS li
                ON li.treatment_id = lo.treatment_id
                WHERE li.treatment_id = :tid                          
                ");               
        $STH->bindParam(':tid', $tid); 
        $STH->execute();
        return $STH->fetchColumn();
    } 
    
    function get_new_tid() {
        $STH = $this->DBH->prepare("SELECT MAX(treatment_id) + 1 FROM `treatment_list` ");        
        $STH->execute();
        $tid = $STH->fetchColumn();
        if ($tid == '' || $tid == '0') {
            return '1';
        } else {
            return $tid;
        }       
    } 
    
    function add_new($tid, $pid) {
        $STH = $this->DBH->prepare("INSERT INTO `treatment_list` VALUES (:tid, :pid)");    
        $STH->bindParam(':tid', $tid);         
        $STH->bindParam(':pid', $pid);       
        $STH->execute();         
    }
    
    function log($tid, $vid, $entry_date, $entry_time, $entry_status, $remarks) {  
        $STH = $this->DBH->prepare("INSERT INTO `treatment_logs` VALUES (
            :treatment_id,
            :version_id,
            :entry_date,
            :entry_time,
            :updated_by,
            :entry_status,
            :remarks
            )");        
        $STH->bindParam(':treatment_id', $tid);   
        $STH->bindParam(':version_id', $vid);        
        $STH->bindParam(':entry_date', $entry_date); 
        $STH->bindParam(':entry_time', $entry_time); 
        $STH->bindParam(':updated_by', $_SESSION['person_id']); 
        $STH->bindParam(':entry_status', $entry_status); 
        $STH->bindParam(':remarks', $remarks); 
        $STH->execute();          
        
    }             
    
    // 20150216 customized function to get the previous post hd weight, 
    // checks the latest treatent id and gets the second to the last post hd weight value
    // moved from model_person.php to model_treatment.php 20150514
    function get_prev_data($id, $tid, $property_id) {
        $STH = $this->DBH->prepare("SELECT treatment_id AS tid,
            (SELECT VALUE FROM `treatments` 
            WHERE treatment_id = tid 
            AND property_id = :property_id 
            ORDER BY version_id DESC LIMIT 1) AS VALUE            
            FROM `treatment_list` AS li
            WHERE person_id = :id
            AND treatment_id <= :tid
            ORDER BY treatment_id DESC LIMIT 1,1");
        $STH->bindParam(':id', $id);  
        $STH->bindParam(':tid', $tid);
        $STH->bindParam(':property_id', $property_id);
        $STH->execute();
        return  $STH->fetchColumn(1);
    }   
    
    function get_data($tid, $property_id) {
        $STH = $this->DBH->prepare("SELECT VALUE 
            FROM `treatments`  AS e
            INNER JOIN treatment_list AS li
            ON e.treatment_id = li.treatment_id
            WHERE li.treatment_id = :tid
            AND property_id = :property_id
            ORDER BY version_id DESC LIMIT 1");        
        $STH->bindParam(':tid', $tid);
        $STH->bindParam(':property_id', $property_id);
        $STH->execute();
        return $STH->fetchColumn();
    }   
    
    function get_latest_data($pid, $property_id) {
        $STH = $this->DBH->prepare("SELECT VALUE
            FROM `treatments`  AS e
            INNER JOIN treatment_list AS li
            ON e.treatment_id = li.treatment_id
            WHERE person_id = :pid
            AND property_id = :property_id
            ORDER BY li.treatment_id DESC,version_id DESC 
            LIMIT 1");        
        $STH->bindParam(':pid', $pid);
        $STH->bindParam(':property_id', $property_id);
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function get_status($id, $tid) {
        $STH = $this->DBH->prepare("SELECT entry_status FROM `treatment_logs` AS lo
            INNER JOIN treatment_list AS li 
            ON lo.treatment_id = li.treatment_id
            WHERE person_id = :id
            AND li.treatment_id = :tid         
            ORDER BY version_id DESC
            LIMIT 1");      
        $STH->bindParam(':tid', $tid );
        $STH->bindParam(':id', $id );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }
    
    function get_status_time($id, $entry_date, $entry_status) {
        $STH = $this->DBH->prepare("SELECT MAX(entry_time) FROM 
        `treatment_logs` AS lo
        INNER JOIN `treatment_list` AS li
        ON lo.treatment_id = li.treatment_id
        WHERE entry_status = :entry_status
        AND entry_date = :entry_date
        AND person_id = :id");    
        $STH->bindParam(':entry_status', $entry_status); 
        $STH->bindParam(':entry_date', $entry_date);        
        $STH->bindParam(':id', $id );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }     
    
    function generate_time_intervals($duration, $interval, $time_start) {
        $tmp = date("H:i", strtotime("$time_start")); 
        switch($interval) {
            case 10:
                $m = 6;
                break;
            case 15: 
                $m = 4;
                break;
            case 30: 
                $m = 2;
                break;           
        }
        
        $intervals = array();
        for ($i=0; $i <= $duration * $m; $i++) {            
            $intervals[$i] = $tmp;
            $tmp = date("H:i", strtotime("$tmp +$interval minutes" ));           
        } 
        return $intervals;       
    }    
    
    function get_monitoring_sheet($tid) {  
        $STH = $this->DBH->prepare("SELECT * FROM `monitoring_sheet` WHERE treatment_id = :tid");        
        $STH->bindParam(':tid', $tid);
        $STH->execute(); 
        return $STH->fetchAll();       
    }
    
    
    // 20150212 multi option capable
    // 20150514 optimizations
    // 20151006 quick fix, for faster loading
    function get_info($tid, $vid) { 
        if (!$this->id_exists($tid) || $vid == '1') {
            $query = "SELECT p.property_id AS property_id,
            `property_name`,
            h.modify_options AS modify_options, 
            `html_type`,            
            `label_font`,
            `label_font_size`,
            `label_font_weight`,
            `pos_label_top`,
            `pos_label_left`,
            `pos_input_top`,
            `pos_input_left`,
            `input_width`,
            `input_height`,           
            auto_display,
            '' AS property_value
            FROM `treatment_properties` AS p
            INNER JOIN `html` AS  h
            ON p.property_id = h.property_id      
            WHERE active = '1'";           
        } else {
            $query = "SELECT 
            t.property_id AS property_id,
            `property_name`,
            h.modify_options AS modify_options, 
            `html_type`,            
            `label_font`,
            `label_font_size`,
            `label_font_weight`,
            `pos_label_top`,
            `pos_label_left`,
            `pos_input_top`,
            `pos_input_left`,
            `input_width`,
            `input_height`,           
            auto_display,
            `value` as property_value
            FROM `treatments` AS t  
            INNER JOIN treatment_properties AS tp
            ON t.property_id = tp.property_id
            INNER JOIN html AS h
            ON t.property_id = h.property_id
            WHERE treatment_id = :tid AND version_id = :vid       
            AND active = '1'";
        }    
        
        
        $STH = $this->DBH->prepare($query); 
        $STH->bindParam(':vid', $vid); 
        $STH->bindParam(':tid', $tid);          
        $STH->execute();
        return $STH->fetchAll();        
    }
    
    function get_misc_html($section) {  
        $STH = $this->DBH->prepare("SELECT * FROM `html_misc` WHERE section = :section");          
        $STH->bindParam(':section', $section);          
        $STH->execute();
        return $STH->fetchAll();        
    }
    
    // moved from model_person.php to model_treatment.php 20150514
    function get_history($id) {  
        $STH = $this->DBH->prepare("SELECT treatment_id, treatment_id AS tid,
            (SELECT entry_date FROM `treatment_logs`  WHERE treatment_id = tid ORDER BY entry_date ASC LIMIT 1) AS entry_date,
            (SELECT DATE_FORMAT(CONCAT(SUBSTR(entry_date,1,4),'-',SUBSTR(entry_date,5,2),'-',SUBSTR(entry_date,7,2)),'%M %d, %Y') 
                FROM `treatment_logs`  WHERE treatment_id = tid ORDER BY entry_date ASC LIMIT 1) AS entry_date_readable,
            (SELECT MAX(version_id) FROM `treatment_logs`  WHERE treatment_id = tid) AS version_id
            FROM `treatment_list` 
            WHERE person_id = :id
            ");        
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetchAll();       
    }  
    
    // this is used to list all options, not per property id
    function get_options() {  
        $STH = $this->DBH->prepare("SELECT * FROM `treatment_options` WHERE active = '1'");     
        $STH->execute();
        return $STH->fetchAll();       
    }
    
    function get_options_persons() {  
        $STH = $this->DBH->prepare("SELECT * FROM persons AS p
            INNER JOIN person_credentials AS c
            ON p.person_id = c.person_id
            INNER JOIN treatment_options_persons AS o
            WHERE c.user_role = o.user_role
            AND c.user_level <= o.user_level
            AND c.active = '1'");     
        $STH->execute();
        return $STH->fetchAll();       
    }
    
    function get_first_date($id) {
        $STH = $this->DBH->prepare("SELECT entry_date 
            FROM `treatment_logs` AS tlo
            INNER JOIN `treatment_list` AS tli
            ON tlo.treatment_id = tli.treatment_id
            WHERE person_id = :id
            ORDER BY entry_date ASC
            LIMIT 1");   
        $STH->bindParam(':id', $id );
        $STH->execute(); 
        return  $STH->fetchColumn(); 
    }    
    
    //======================================================================
    // Below are functions used for saving the treatment form
    
    function delete_entries($tid, $vid) {  
        $STH = $this->DBH->prepare("DELETE FROM `treatments` WHERE treatment_id = :treatment_id AND version_id = :version_id");    
        $STH->bindParam(':treatment_id', $tid);   
        $STH->bindParam(':version_id', $vid);                 
        $STH->execute();        
    }  
    
    function add_entries($tid, $vid, $property_id, $value) {  
        $STH = $this->DBH->prepare("INSERT INTO `treatments` VALUES (
            :treatment_id,
            :version_id,
            :property_id,            
            :value
            )");    
        $STH->bindParam(':treatment_id', $tid);   
        $STH->bindParam(':version_id', $vid);   
        $STH->bindParam(':property_id', $property_id);         
        $STH->bindParam(':value', $value);                   
        $STH->execute();        
    }   

    function save_monitoring_sheet($tid, $ms_time, $ms_bp1, $ms_bp2, $ms_ap, $ms_vp, $ms_qb, $ms_tmp, $ms_ufvol, $ms_comments) { 

        $STH = $this->DBH->prepare("INSERT INTO `monitoring_sheet` VALUES (
            :tid,
            :ms_time,
            :ms_bp1,
            :ms_bp2,
            :ms_ap,
            :ms_vp,
            :ms_qb,
            :ms_tmp,
            :ms_ufvol,
            :ms_comments
            )");        
            $STH->bindParam(':tid', $tid);
            $STH->bindParam(':ms_time', $ms_time);
            $STH->bindParam(':ms_bp1', $ms_bp1);
            $STH->bindParam(':ms_bp2', $ms_bp2);
            $STH->bindParam(':ms_ap', $ms_ap);
            $STH->bindParam(':ms_vp', $ms_vp); 
            $STH->bindParam(':ms_qb', $ms_qb);
            $STH->bindParam(':ms_tmp', $ms_tmp);
            $STH->bindParam(':ms_ufvol', $ms_ufvol);
            $STH->bindParam(':ms_comments', $ms_comments);
            $STH->execute();         
    }   
    
    function delete_monitoring_sheet($tid) {  
        $STH = $this->DBH->prepare("DELETE FROM `monitoring_sheet` WHERE treatment_id = :tid");        
        $STH->bindParam(':tid', $tid);
        $STH->execute();         
    } 
    
    //======================================================================
    // Below are functions used for viewing the settings of the treatment form
    
    function list_properties($active_only) {        
        if ($active_only) { $q = " AND active = '1'"; } else { $q = ''; } 
        $STH = $this->DBH->prepare("SELECT p.property_id, p.property_name,h.html_type FROM `treatment_properties` AS p
            INNER JOIN `html` AS h
            ON p.property_id = h.property_id" . $q);    
        $STH->execute();
        return $STH->fetchAll();       
    }
    
    function get_property_values($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `treatment_properties` WHERE property_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);       
    }
    
    function get_property_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `treatment_properties`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }
    
    function get_property_options($id) {  
        $STH = $this->DBH->prepare("SELECT * FROM `treatment_options` WHERE property_id = :id");     
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetchAll();       
    }  
    
    function get_property_options2($id) {  
        $STH = $this->DBH->prepare("SELECT * FROM `treatment_options_persons` WHERE property_id = :id");     
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);   
    }    
    
    function get_property_options2_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `treatment_options_persons`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }
    
    function get_html_values($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `html` WHERE property_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);       
    }    
    
    function get_html_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `html`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }
    
    //======================================================================
    // Below are functions used for saving the settings of the treatment form    
    
    function property_exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `treatment_properties` WHERE property_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function save_property($id, $property_name, $section, $sub_section, $auto_display, $active) {
        if ($id == '') {
            return 0;            
        }           
        if (!$this->property_exists($id)) {           
            $STH = $this->DBH->prepare("INSERT INTO treatment_properties VALUES (
                :id,                  
                :property_name,                
                :section,                      
                :sub_section,                  
                :auto_display,                 
                :active  
                )");
        } else {            
            $STH = $this->DBH->prepare("UPDATE treatment_properties SET                            
                property_name = :property_name,           
                section = :section,                       
                sub_section = :sub_section,              
                auto_display = :auto_display,             
                active = :active      
                WHERE property_id = :id  
                ");
        } 

        $STH->bindParam(':id', $id );                       
        $STH->bindParam(':property_name', $property_name );                   
        $STH->bindParam(':section', $section );                               
        $STH->bindParam(':sub_section', $sub_section );                       
        $STH->bindParam(':auto_display', $auto_display );                     
        $STH->bindParam(':active', $active );             
        return $STH->execute();                
    } 
    
    function save_property_html($id, $html_type, $pos_label_top, $pos_label_left, $label_font, $label_font_size, $label_font_weight, $pos_input_top, $pos_input_left, $input_width, $input_height, $modify_options) {
        if ($id == '') {
            return 0;            
        }           
        if (!$this->property_exists($id)) {           
            $STH = $this->DBH->prepare("INSERT INTO html VALUES (
                :id,                  
                :html_type,                                 
                :pos_label_top,                
                :pos_label_left,               
                :label_font,                   
                :label_font_size,              
                :label_font_weight,            
                :pos_input_top,                
                :pos_input_left,               
                :input_width,                  
                :input_height,                            
                :modify_options  
                )");
        } else {            
            $STH = $this->DBH->prepare("UPDATE html SET                            
                html_type = :html_type,                                    
                pos_label_top = :pos_label_top,                
                pos_label_left = :pos_label_left,              
                label_font = :label_font,                      
                label_font_size = :label_font_size,            
                label_font_weight = :label_font_weight,        
                pos_input_top = :pos_input_top,                
                pos_input_left = :pos_input_left,              
                input_width = :input_width,                    
                input_height = :input_height,
                modify_options = :modify_options              
                WHERE property_id = :id  
                ");
        } 

        $STH->bindParam(':id', $id );                       
        $STH->bindParam(':html_type', $html_type );                           
        $STH->bindParam(':pos_label_top', $pos_label_top );                   
        $STH->bindParam(':pos_label_left', $pos_label_left );                 
        $STH->bindParam(':label_font', $label_font );                         
        $STH->bindParam(':label_font_size', $label_font_size );               
        $STH->bindParam(':label_font_weight', $label_font_weight );           
        $STH->bindParam(':pos_input_top', $pos_input_top );                   
        $STH->bindParam(':pos_input_left', $pos_input_left );                 
        $STH->bindParam(':input_width', $input_width );                       
        $STH->bindParam(':input_height', $input_height );                   
        $STH->bindParam(':modify_options', $modify_options );                             
        return $STH->execute();             
    }
    
    function add_options($id, $property_id, $option_value, $active) {        
        $STH = $this->DBH->prepare("INSERT INTO treatment_options VALUES (
            :id,                    
            :property_id,                  
            :option_value,                 
            :active              
            )");      

        $STH->bindParam(':id', $id );                           
        $STH->bindParam(':property_id', $property_id );                       
        $STH->bindParam(':option_value', $option_value );                     
        $STH->bindParam(':active', $active );
        if ($this->options_exists($id)) {
            return 1;    
        } else {
            return $STH->execute();  
        }
    } 
    
    function delete_options($id) {       
        $STH = $this->DBH->prepare("DELETE FROM treatment_options where property_id = :id");
        $STH->bindParam(':id', $id );                                     
        return $STH->execute();                
    } 
    
    function options_exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `treatment_options` WHERE option_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function options2_exists($id) {
        $STH = $this->DBH->prepare("SELECT COUNT(*) FROM `treatment_options_persons` WHERE property_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function save_options2($id, $user_group, $user_role, $user_level, $active) {
        if ($id == '') {
            return 0;            
        }           
        if (!$this->options2_exists($id)) {           
            $STH = $this->DBH->prepare("INSERT INTO treatment_options_persons VALUES (
                :id,                  
                :user_group,                   
                :user_role,                    
                :user_level,                   
                :active            
                )");
        } else {            
            $STH = $this->DBH->prepare("UPDATE treatment_options_persons SET                           
                user_group = :user_group,                      
                user_role = :user_role,                        
                user_level = :user_level,                      
                active = :active      
                WHERE property_id = :id  
                ");
        } 

        $STH->bindParam(':id', $id );                       
        $STH->bindParam(':user_group', $user_group );                         
        $STH->bindParam(':user_role', $user_role );                           
        $STH->bindParam(':user_level', $user_level );                         
        $STH->bindParam(':active', $active );             
        return $STH->execute();                
    }      
      

   
    
    
    
}

?>
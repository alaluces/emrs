<?php

class lib_person {
    
    function __construct($DBH) {        
        $this->DBH = $DBH;
    }
    
    function get_age($id) { 
        // from http://stackoverflow.com/questions/3776682/php-calculate-age
        //date in mm/dd/yyyy format; or it can be in other formats as well
        //$birthDate = "12/17/1983";
       $STH = $this->DBH->prepare("SELECT birth_date
            FROM persons 
            WHERE person_id = :id 
           ");
        $STH->bindParam(':id', $id); 
        $STH->execute();
        $birth_date = $STH->fetchColumn();
        
        $year  = substr($birth_date, 0, 4);
        $month = substr($birth_date, 4, 2);
        $day   = substr($birth_date, 6, 2);      
        //explode the date to get month, day and year
        //$birthDate = explode("/", $birthDate);
        //get age from date or birthdate
        $age = (date("md", date("U", mktime(0, 0, 0, $month, $day, $year))) > date("md")
        ? ((date("Y") - $year) - 1)
        : (date("Y") - $year));
        
        if ($age > 150) {$age = '0';}        
        return $age;
    }
    
    function get_fullname($id) {
       $STH = $this->DBH->prepare("SELECT concat(fname, ' ', lname) 
            FROM persons 
            WHERE person_id = :id 
           ");
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return  $STH->fetchColumn();
    }
    
    function list_users($active_only) {        
        if ($active_only) { $q = " AND c.active = '1'"; } else { $q = ''; } 
        $STH = $this->DBH->prepare("SELECT c.person_id, CONCAT(fname, ' ', lname) AS full_name, username, user_group, user_role, user_level FROM person_credentials AS c
            INNER JOIN persons AS p
            ON c.person_id = p.person_id" . $q);    
        $STH->execute();
        return $STH->fetchAll();       
    } 
    
    // 20150309 added gender and hepa status
    // 20150312 optimize hepa status search
    function find($param, $gender, $hepa_status, $active) {
        $gender      = $gender . '%';
        $hepa_status = '%' . $hepa_status;
        $active      = $active . '%';
        $tmp = explode('-', $param);
        if (count($tmp) > 1) {            
            $lname = $tmp[0] . '%';
            $fname = $tmp[1] . '%'; 
            $STH = $this->DBH->prepare("SELECT p.person_id, fname, lname, city, province, birth_date                       
                        FROM persons AS p
                        LEFT JOIN `patient_details` AS pd
                        ON p.person_id = pd.person_id
                        WHERE fname LIKE :fname 
                        AND lname LIKE :lname
                        AND gender LIKE :gender
                        AND active LIKE :active
                        AND pd.hepa_status LIKE :hepa_status ORDER BY lname");            
            $STH->bindParam(':fname', $fname);     
        } else {
            $lname = $tmp[0] . '%'; 
            $STH = $this->DBH->prepare("SELECT p.person_id, fname, lname, city, province, birth_date                       
                            FROM persons AS p
                            LEFT JOIN `patient_details` AS pd
                            ON p.person_id = pd.person_id
                            WHERE lname LIKE :lname
                            AND gender LIKE :gender
                            AND active LIKE :active
                            AND pd.hepa_status LIKE :hepa_status ORDER BY lname");                        
        }
        
        $STH->bindParam(':lname', $lname); 
        $STH->bindParam(':gender', $gender); 
        $STH->bindParam(':active', $active); 
        $STH->bindParam(':hepa_status', $hepa_status); 
        $STH->execute();
        return $STH->fetchAll();        
    }
       
    function get_credentials($id) {
        $STH = $this->DBH->prepare("SELECT person_id, username, user_group, user_role, user_level, active FROM person_credentials WHERE person_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return  $STH->fetch();
    }
    
    function prepare($person_id) {                 
        $STH = $this->DBH->prepare("INSERT INTO persons VALUES (
                :person_id, '', '', '', '', '', '', '', '', '', '',  '', '1')");
        $STH->bindParam(':person_id', $person_id );
        return $STH->execute();       
    }
    
    function save($person_id, $fname, $mname, $lname, $gender, $birth_date, $civil_status, $address1, $address2, $city, $province, $phone_number, $active) {
        if ($fname == '' || $lname == '' || $birth_date == '' || $gender == '') {
            return 0;            
        } else {            
            if (!$this->exists($person_id)) {            
                $STH = $this->DBH->prepare("INSERT INTO persons VALUES (
                    :person_id,                    
                    :fname,                        
                    :mname,                        
                    :lname,                        
                    :gender,                       
                    :birth_date,                   
                    :civil_status,                 
                    :address1,                     
                    :address2,                     
                    :city,                         
                    :province,                     
                    :phone_number,                 
                    :active   
                    )");
            } else {                               
                $STH = $this->DBH->prepare("UPDATE persons SET                         
                    fname = :fname,                                
                    mname = :mname,                                
                    lname = :lname,                                
                    gender = :gender,                              
                    birth_date = :birth_date,                      
                    civil_status = :civil_status,                  
                    address1 = :address1,                          
                    address2 = :address2,                          
                    city = :city,                                  
                    province = :province,                          
                    phone_number = :phone_number,                  
                    active = :active                        
                    WHERE person_id = :person_id                
                    ");
              
            }  
            $STH->bindParam(':person_id', $person_id );                           
            $STH->bindParam(':fname', $fname );                                   
            $STH->bindParam(':mname', $mname );                                   
            $STH->bindParam(':lname', $lname );                                   
            $STH->bindParam(':gender', $gender );                                 
            $STH->bindParam(':birth_date', $birth_date );                         
            $STH->bindParam(':civil_status', $civil_status );                     
            $STH->bindParam(':address1', $address1 );                             
            $STH->bindParam(':address2', $address2 );                             
            $STH->bindParam(':city', $city );                                     
            $STH->bindParam(':province', $province );                             
            $STH->bindParam(':phone_number', $phone_number );                     
            $STH->bindParam(':active', $active );             
            return $STH->execute();                     
        }
        
    }    
    
    function save_patient($person_id, $dry_weight, $physician_id, $hepa_status, $first_dialysis, $diagnosis, $blood_type, $philhealth_number) {
        if ($dry_weight == '' || $physician_id == '' || $hepa_status == '' ) {
            return 0;            
        } else {            
            if (!$this->patient_exists($person_id)) {            
                $STH = $this->DBH->prepare("INSERT INTO `patient_details` VALUES (
                    :person_id,                    
                    :dry_weight,                   
                    :physician_id,                 
                    :hepa_status,
                    :first_dialysis, 
                    :diagnosis, 
                    :blood_type,                    
                    :philhealth_number                    
                    )");
            } else {                               
                $STH = $this->DBH->prepare("UPDATE `patient_details` SET                                           
                    dry_weight = :dry_weight,                      
                    physician_id = :physician_id,                  
                    hepa_status = :hepa_status,
                    first_dialysis = :first_dialysis, 
                    diagnosis = :diagnosis, 
                    blood_type = :blood_type,                     
                    philhealth_number = :philhealth_number                  
                    WHERE person_id = :person_id                
                    ");
              
            }  
            $STH->bindParam(':person_id', $person_id );                           
            $STH->bindParam(':dry_weight', $dry_weight );                         
            $STH->bindParam(':physician_id', $physician_id );                     
            $STH->bindParam(':hepa_status', $hepa_status );  
            $STH->bindParam(':first_dialysis', $first_dialysis );
            $STH->bindParam(':diagnosis', $diagnosis );
            $STH->bindParam(':blood_type', $blood_type );            
            $STH->bindParam(':philhealth_number', $philhealth_number );            
            return $STH->execute();                     
        }
        
    }      
     
    function save_pro_data($id, $pro_title, $pro_affiliation, $prc_id, $license_number, $ptr, $s2, $active) {
        
        if (!$this->pro_data_exists($id)) { 
            
            $STH = $this->DBH->prepare("INSERT INTO person_pro_data VALUES (
                :person_id,                    
                :pro_title,                    
                :pro_affiliation,              
                :prc_id,                       
                :license_number,               
                :ptr,                          
                :s2,                           
                :active  
                )");
        } else {  
            
            $STH = $this->DBH->prepare("UPDATE person_pro_data SET                                   
                pro_title = :pro_title,                        
                pro_affiliation = :pro_affiliation,            
                prc_id = :prc_id,                              
                license_number = :license_number,              
                ptr = :ptr,                                    
                s2 = :s2,                                      
                active = :active     
                WHERE person_id = :person_id  
                ");
        }  

        $STH->bindParam(':person_id', $id );                           
        $STH->bindParam(':pro_title', $pro_title );                           
        $STH->bindParam(':pro_affiliation', $pro_affiliation );               
        $STH->bindParam(':prc_id', $prc_id );                                 
        $STH->bindParam(':license_number', $license_number );                 
        $STH->bindParam(':ptr', $ptr );                                       
        $STH->bindParam(':s2', $s2 );                                         
        $STH->bindParam(':active', $active );           
        return $STH->execute();        
    }    
    
    function save_credentials($id, $username, $user_group, $user_role, $user_level, $active) {
        if ($username == '' || $id == '') {
            return 0;            
        }   
        
        if (!$this->credentials_exists($id)) { 
            
            $STH = $this->DBH->prepare("INSERT INTO person_credentials VALUES (
                :id, 
                :username, 
                '',
                :user_group, 
                :user_role, 
                :user_level, 
                :active
                )");
        } else {  
            
            $STH = $this->DBH->prepare("UPDATE person_credentials SET                 
                username        = :username,                 
                user_group      = :user_group, 
                user_role       = :user_role, 
                user_level      = :user_level, 
                active          = :active 
                WHERE person_id = :id  
                ");
        }  

        $STH->bindParam(':id', $id );
        $STH->bindParam(':username', $username );        
        $STH->bindParam(':user_group', $user_group);
        $STH->bindParam(':user_role', $user_role);
        $STH->bindParam(':user_level', $user_level);               
        $STH->bindParam(':active', $active);             
        $STH->execute();  
        return 1 ;            
        
        
    }      
    
    function save_password($id, $password_hash) {
        if ($password_hash == '' || $id == '') {
            return 0;            
        } 
            
        $STH = $this->DBH->prepare("UPDATE person_credentials SET                 
            password_hash   = :password_hash
            WHERE person_id = :id  
            ");  

        $STH->bindParam(':id', $id );    
        $STH->bindParam(':password_hash', $password_hash);         
        $STH->execute();  
        return 1 ;       
    }    
     
    function exists($id) {
        $STH = $this->DBH->prepare("SELECT count(*) FROM persons WHERE person_id = :person_id");
        $STH->bindParam(':person_id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }
    
    function patient_exists($id) {
        $STH = $this->DBH->prepare("SELECT count(*) FROM patient_details WHERE person_id = :person_id");
        $STH->bindParam(':person_id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function username_exists($username) {
        $STH = $this->DBH->prepare("SELECT count(*) FROM person_credentials WHERE username = :username");
        $STH->bindParam(':username', $username);     
        $STH->execute();
        return $STH->fetchColumn();
    }    
    
    function credentials_exists($id) {
        $STH = $this->DBH->prepare("SELECT count(*) FROM person_credentials WHERE person_id = :person_id");
        $STH->bindParam(':person_id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    } 
    
    function pro_data_exists($id) {
        $STH = $this->DBH->prepare("SELECT count(*) FROM person_pro_data WHERE person_id = :person_id");
        $STH->bindParam(':person_id', $id);     
        $STH->execute();
        return $STH->fetchColumn();
    }     
       
    function get_new_id() {
        $STH = $this->DBH->prepare("SELECT MAX(person_id) + 1 FROM persons");          
        $STH->execute();
        return $STH->fetchColumn();         
    }
    

    
    //============================================================================================ 
    // 20150325 patient info rewrite
    
    // used only by treatments
    // 20150327 added dry weight, hepa status and physician
    // other modules use get_patient_header() and get_person_values($id)
    function get_info($id) {
        $STH = $this->DBH->prepare("SELECT p.*,pd.*,ppd.pro_title AS physician FROM `persons` AS p
        INNER JOIN patient_details AS pd
        ON p.person_id = pd.person_id
        INNER JOIN `person_pro_data` AS ppd
        ON pd.physician_id = ppd.person_id
        WHERE p.person_id = :id");
        $STH->bindParam(':id', $id);     
        $STH->execute();
        return  $STH->fetch();
    }
        
    // 20150327 used to prevent duplicate entry caused by double click bug
    // 20150422 already solved by by setting the person id agad
    function check_duplicate($fname, $mname, $lname, $gender, $birth_date, $civil_status, $address1, $address2, $city, $province, $phone_number) {
      
    }    
    
    function get_person_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `persons`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }
    
    function get_pro_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `person_pro_data`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }    
    
    function get_patient_header() {  
        $STH = $this->DBH->prepare("SHOW FULL FIELDS FROM `patient_details`");       
        $STH->execute();
        return $STH->fetchALL(PDO::FETCH_ASSOC);   
    }  
    
    function get_person_values($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `persons` WHERE person_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);       
    }  
    
    function get_patient_values($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `patient_details` WHERE person_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);       
    }   

    function get_pro_values($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `person_pro_data` WHERE person_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch(PDO::FETCH_ASSOC);       
    }    
    
    function get_person_options() {       
        $STH = $this->DBH->prepare("SELECT * FROM `person_options`");     
        $STH->execute();
        return $STH->fetchAll();       
    }   
    
    // A Hack so we can tell which are selects from the textboxes
    //SELECT property_name FROM `person_options`GROUP BY property_name
    //SELECT GROUP_CONCAT(DISTINCT property_name) FROM `person_options`
    // 20150506 Just indicate if gender or hepa status, its much easier
    //function get_in_select() {     
    //}  
    
    function get_pro_info($id) {       
        $STH = $this->DBH->prepare("SELECT * FROM `person_pro_data` WHERE person_id = :id");      
        $STH->bindParam(':id', $id); 
        $STH->execute();
        return $STH->fetch();       
    } 
    
    function get_physicians() {
        $STH = $this->DBH->prepare("SELECT pc.person_id, pro_title 
        FROM `person_credentials` AS pc
        INNER JOIN person_pro_data AS ppd
        ON pc.person_id = ppd.person_id
        WHERE user_role = 'doctor'
        AND pc.active = '1'");        
        $STH->execute();
        return $STH->fetchAll();
   
    }
    
    function get_physician_id($presc_id) {
        $STH = $this->DBH->prepare("SELECT physician_id FROM `presc_list` WHERE presc_id = :presc_id");
        $STH->bindParam(':presc_id', $presc_id);           
        $STH->execute();
        return $STH->fetchColumn();
    } 
    
    function get_patients() {
        $STH = $this->DBH->prepare("SELECT p.person_id, CONCAT(fname, ' ', lname) as full_name FROM `persons` AS p 
            INNER JOIN `patient_details` AS pd
            ON p.person_id = pd.person_id
            WHERE active = '1' ORDER BY lname");        
        $STH->execute();
        return $STH->fetchAll();
   
    }    
    
    
    
    
    
}

?>
<?php

class lib_sec {
    
    function __construct($DBH, $app) {        
        $this->DBH = $DBH;
        $this->app = $app;
    }
    
    function try_login($username, $password) {        
        $STH = $this->DBH->prepare("select person_id, username, user_level from person_credentials 
            where username = :username and password_hash = :password_hash and active='1' limit 1");
        $STH->bindParam(':username', $username);
        $STH->bindParam(':password_hash', $this->password_hash($password));
        $STH->execute();        
        return $STH->fetch();  
    }    

    function login($username, $password) {        
        $tmp = $this->try_login($username, $password);
        if ($tmp) {
            $this->set_session_credentials($tmp);
            $this->set_session_settings($this->get_settings());
            return true;
        } else {
            return false;
        }  
    }
    
    function set_session_credentials($arr_details) {
        foreach ($arr_details as $key => $value) {            
           $_SESSION[$key]  = $value;        
        }
    }    
    
    function set_session_settings($arr_settings) {
        foreach ($arr_settings as $key => $value) {            
           $_SESSION[$key] = $value;            
        }
    }  
    
    function save_settings($keys, $vals) {
        $this->delete_settings();
        $i = 0;
        foreach ($keys as $value) {
            if ($vals[$i] == '') { continue; }
            $this->add_settings($value, $vals[$i]);
            $i++;
        }        
    }  
    
    function add_settings($key, $val) {         
        $STH = $this->DBH->prepare("INSERT INTO `app_settings` VALUES (:key, :val) ");                                   
        $STH->bindParam(':key', $key );                             
        $STH->bindParam(':val', $val );                 
        return $STH->execute();          
    }    
    
    function delete_settings() {         
        $STH = $this->DBH->prepare("DELETE FROM `app_settings`");                
        return $STH->execute();          
    }    
    
    function get_session_array() {
        $a = array();
        foreach ($_SESSION as $key => $value) {            
           $a[$key] = $value;                
        }
        return $a;        
    } 
    
    function get_settings() {
        $STH = $this->DBH->prepare("SELECT * FROM `app_settings`");        
        $STH->execute();
        $t = $STH->fetchAll();
        $a = array();
        
        // this loop converts it from multi dimensional array into key=>val array
        foreach ($t as $value) {            
           $a[$value[0]] = $value[1];            
        }
        return $a;       
    } 
    
    function get_acl($section) {
        $STH = $this->DBH->prepare("SELECT * FROM `acl` WHERE section = :section ");
        $STH->bindParam(':section', $section); 
        $STH->execute();
        return $STH->fetch();       
    }  
    
    function list_acl() {
        $STH = $this->DBH->prepare("SELECT section, user_level FROM `acl`");        
        $STH->execute();
        $t = $STH->fetchAll();
        $a = array();
        
        // this loop converts it from multi dimensional array into key=>val array
        foreach ($t as $value) {            
           $a[$value[0]] = $value[1];            
        }
        return $a;       
    } 
    
    function save_acl($keys, $vals) {
        $this->delete_acl();
        $i = 0;
        foreach ($keys as $value) {
            if ($vals[$i] == '') { continue; }
            $this->add_acl($value, $vals[$i]);
            $i++;
        }        
    }  
    
    function add_acl($key, $val) {         
        $STH = $this->DBH->prepare("INSERT INTO `acl` VALUES (:key, :val, '') ");                                   
        $STH->bindParam(':key', $key );                             
        $STH->bindParam(':val', $val );                 
        return $STH->execute();          
    }    
    
    function delete_acl() {         
        $STH = $this->DBH->prepare("DELETE FROM `acl`");                
        return $STH->execute();          
    }    
    
    // used on treatment form
    function authenticate_by_id($id, $password) {        
        $STH = $this->DBH->prepare("select username from person_credentials 
            where person_id = :id and password_hash = :password_hash and active='1' limit 1");
        $STH->bindParam(':id', $id);
        $STH->bindParam(':password_hash', $this->password_hash($password));
        $STH->execute();        
        return $STH->fetchColumn();   
    }    
    
    function password_hash($password) { 
        $password = filter_var($password);
        return md5(md5($password . md5($password . $password)));
    }   
    
    function is_logged_in() {
        if (isset($_SESSION['username'])) {
            return 1;
        } else {
            return 0;
        }    
    } 
    
    // general acl - check user level and is logged in, redirector
    // replaces if (!$sec->is_logged_in() ) { $app->redirect("/emrs/emrs/login"); }
    function check($section) {
        if (!$this->is_logged_in() ) { $this->app->redirect("/emrs/emrs/login"); } 
        
        $acl = $this->get_acl($section);
        
        if (!$acl ) { $this->app->redirect("/emrs/emrs/restricted"); } 
        
        if ($_SESSION['user_level'] >= $acl[1]) {
            if ($acl[2] == 'DENY') {
                $this->app->redirect("/emrs/emrs/restricted");                
            }           
        } else {
            $this->app->redirect("/emrs/emrs/restricted");    
        }        
    }     
}

class lib_misc {    
    function display_phpinfo() {
        phpinfo();
    }    
    
    function range ($start,$end) {
        for ($i=$start;$i<=$end;$i++) {
            echo "$i<br>";           
        }        
    }
    
    public function get_uploads_dir($type) {
        $delim = '\\';              
        $t = explode($delim, getcwd());
        $x ='';
        for ($i=0; $i < count($t) - 1; $i++) {             
            $x .= $t[$i] . $delim;
        } 
        return $x . 'uploads' . "$delim$type$delim" ;     
    }
    
    public function build_uploads_dir($dirs) {
        //$mode = '0777';      
       
        foreach ($dirs as $dir) {
            $temp_dir = $this->get_uploads_dir($dir);
            if (!file_exists($temp_dir)) {
                // note: mkdir -p is windows and linux compatible
                exec("mkdir -p $temp_dir");    
            }
            unset($temp_dir);
        }        
    }
    
}

?>
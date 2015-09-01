<?php

class lib_sec {
    
    function __construct($DBH, $app) {        
        $this->DBH = $DBH;
        $this->app = $app;
    }

    function login($username, $password) {        
        $STH = $this->DBH->prepare("select person_id, username, user_level from person_credentials 
            where username = :username and password_hash = :password_hash and active='1' limit 1");
        $STH->bindParam(':username', $username);
        $STH->bindParam(':password_hash', $this->password_hash($password));
        $STH->execute();
        
        $tmp = $STH->fetch();
        if ($tmp) {
            $this->set_session_credentials($tmp);
            $this->set_session_settings($this->get_settings());
            return $tmp;
        } else {
            return 0;
        }  
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
    
    function set_session($arr_details) {
        $_SESSION['person_id']  = $arr_details[0];
        $_SESSION['username']   = $arr_details[1];
        $_SESSION['user_level'] = $arr_details[2];
    }
    
    function get_session_array() {
        return array(
            'person_id'  => $_SESSION['person_id'],
            'username'   => $_SESSION['username'] ,
            'user_level' => $_SESSION['user_level']
        );  
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
    
    function get_acl($section) {
        $STH = $this->DBH->prepare("SELECT * FROM `acl` WHERE section = :section ");
        $STH->bindParam(':section', $section); 
        $STH->execute();
        return $STH->fetch();       
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

    function set_session_credentials($arr_details) {
        $_SESSION['person_id']  = $arr_details[0];
        $_SESSION['username']   = $arr_details[1];
        $_SESSION['user_level'] = $arr_details[2];
    }    
    
    function set_session_settings($arr_settings) {
        foreach ($arr_settings as $key => $value) {            
           $_SESSION[$key] = $value;            
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
    
    public function get_profile_pic_dir() {
        $delim = '\\';              
        $t = explode($delim, getcwd());
        $x ='';
        for ($i=0; $i < count($t) - 1; $i++) {             
            $x .= $t[$i] . $delim;
        } 
        return $x . 'uploads' . $delim . 'profile_pic' . $delim ;     
    }    
}

?>
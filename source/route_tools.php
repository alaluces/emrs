<?php

$app->group('/tools', function () use ($app, $sec, $person, $treatment, $meds) {
    
    $app->group('/settings', function () use ($app, $sec) {
        
        $app->get('/', function () use ($app, $sec) {    
            $sec->check('settings');            
            $app->render('tools_settings.html', array(
                'title' => 'Settings',      
                'settings' => $sec->get_settings(),
                'session' => $sec->get_session_array()      
            ));       
        }); 
        
        $app->post('/save', function () use ($app, $sec) {
            $sec->check('settings');       
            $keys = $app->request->post('keys');                                    
            $vals = $app->request->post('vals');  
            
            //$allPostVars = $app->request->post();
            //var_dump($allPostVars);             
            
            $sec->save_settings($keys, $vals);
            
            $app->flash('info', "Settings saved." );
            $app->redirect("/emrs/emrs/tools/settings"); 
        });         
        
        
        
        
        
        
        
        
        
        
        
        
    });        
        
    
    $app->group('/users', function () use ($app, $sec, $person) {
        
        $app->get('/active/', function () use ($app, $sec, $person) {    
            $sec->check('users');            
            $app->render('tools_users.html', array(
                'title' => 'Users',
                'person_header' => $person->get_person_header(), 
                'person_options' => $person->get_person_options(),                
                'users' => $person->list_users(1),
                'new_id' => $person->get_new_id(),
                'pro_header' => $person->get_pro_header(),
                'session' => $sec->get_session_array()                   
            ));       
        });
        
        $app->get('/all/', function () use ($app, $sec, $person) {    
            $sec->check('users');            
            $app->render('tools_users.html', array(
                'title' => 'Users', 
                'person_header' => $person->get_person_header(),
                'person_options' => $person->get_person_options(),
                
                'users' => $person->list_users(0),
                'new_id' => $person->get_new_id(),
                'pro_header' => $person->get_pro_header(),
                'session' => $sec->get_session_array()                   
            ));       
        });         
        
        $app->get('/:id', function ($id) use ($app, $sec, $person) {    
            $sec->check('users');      
  
            $app->render('tools_users.html', array(
                'title' => 'Users', 
                'pid' => $id,                
                'cred' => $person->get_credentials($id),
                'person_header' => $person->get_person_header(),               
                'person_values' => $person->get_person_values($id),
                'pro_header' => $person->get_pro_header(),
                'pro_values' => $person->get_pro_values($id),
                'person_options' => $person->get_person_options(),
                
                             
                'users' => $person->list_users(1),
                'session' => $sec->get_session_array()                  
            ));       
        });  
        
        $app->post('/save', function () use ($app, $sec, $person) {
            $sec->check('users');        
            $id           = $app->request->post('pid');
            $fname        = $app->request->post('fname');
            $mname        = $app->request->post('mname');
            $lname        = $app->request->post('lname');
            $gender       = $app->request->post('gender');
            $birth_date   = $app->request->post('birth_date');
            $address1     = $app->request->post('address1');
            $address2     = $app->request->post('address2');
            $city         = $app->request->post('city');
            $province     = $app->request->post('province');
            $phone_number = $app->request->post('phone_number');
            $active       = $app->request->post('active');  
            
            $user_name    = $app->request->post('user_name');
            $user_group   = $app->request->post('user_group');
            $user_role    = $app->request->post('user_role');
            $user_level   = $app->request->post('user_level');
            $pass1        = $app->request->post('pass1');
            $pass2        = $app->request->post('pass2');
            $cpass1       = $app->request->post('cpass1');
            $cpass2       = $app->request->post('cpass2');
            
            $pro_title       = $app->request->post('pro_title');                              
            $pro_affiliation = $app->request->post('pro_affiliation');                  
            $prc_id          = $app->request->post('prc_id');                                    
            $license_number  = $app->request->post('license_number');                    
            $ptr             = $app->request->post('ptr');                                          
            $s2              = $app->request->post('s2');        
                    
            if ($active == '') { $active  = '1'; } 
         
            $ret = $person->save($id, $fname, $mname, $lname, $gender, $birth_date, '', $address1, $address2, $city, $province, $phone_number, $active);
            if (!$ret) {
                $app->flash('error', 'Error: Invalid input');
                $app->redirect("/emrs/emrs/tools/users/$id"); 
            }               
            
            $ret = $person->save_credentials($id, $user_name, $user_group, $user_role, $user_level, $active);
            if (!$ret) {
                $app->flash('error', 'Error: Invalid input');
                $app->redirect("/emrs/emrs/tools/users/$id"); 
            }  
            
            $ret = $person->save_pro_data($id, $pro_title, $pro_affiliation, $prc_id, $license_number, $ptr, $s2, $active);
            if (!$ret) {
                $app->flash('error', 'Error: Invalid input');
                $app->redirect("/emrs/emrs/tools/users/$id"); 
            }            

            // change password
            if ($cpass1 && $cpass2) {

                if (strlen($pass1) < 8 || strlen($pass2) < 8) {
                    $app->flash('error', 'Error: Invalid password length');
                    $app->redirect("/emrs/emrs/tools/users/$id");                 
                }                
                if ($pass1 != $pass2) {
                    $app->flash('error', 'Error: Passwords do not match');
                    $app->redirect("/emrs/emrs/tools/users/$id");                 
                }               
                $password_hash = $sec->password_hash($pass2);                
                $person->save_password($id, $password_hash);
            }      
            
            $app->flash('info', "Information for $fname $lname saved." );
            $app->redirect("/emrs/emrs/tools/users/$id"); 
        });        
    
    });    
    
    $app->group('/treatment-items', function () use ($app, $sec, $treatment) {
        
        $app->get('/active/', function () use ($app, $sec, $treatment) {    
            $sec->check('properties');            
            $app->render('tools_treatment_items.html', array(
                'title' => 'Treatment Items',               
                'properties' => $treatment->list_properties(1),       
                'properties_header' => $treatment->get_property_header(),
                'properties_options2_header' => $treatment->get_property_options2_header(),            
                'html_header' => $treatment->get_html_header(),
                'session' => $sec->get_session_array()                   
            ));     
        });     
        
        $app->get('/all/', function () use ($app, $sec, $treatment) {    
            $sec->check('properties');            
            $app->render('tools_treatment_items.html', array(
                'title' => 'Treatment Items',           
                'properties' => $treatment->list_properties(0),       
                'properties_header' => $treatment->get_property_header(),
                'properties_options2_header' => $treatment->get_property_options2_header(),            
                'html_header' => $treatment->get_html_header(),
                'session' => $sec->get_session_array()                   
            ));      
        });   
        
        $app->get('/:id', function ($id) use ($app, $sec, $treatment) {    
            $sec->check('properties');            
            $app->render('tools_treatment_items.html', array(
                'title' => 'Treatment Items',  
                'id' => $id,   
                'properties' => $treatment->list_properties(1),               
                'properties_values' => $treatment->get_property_values($id),
                'properties_header' => $treatment->get_property_header(),
                'properties_options' => $treatment->get_property_options($id),
                'properties_options2' => $treatment->get_property_options2($id),
                'properties_options2_header' => $treatment->get_property_options2_header(),
                'html_values' => $treatment->get_html_values($id),
                'html_header' => $treatment->get_html_header(),
                'session' => $sec->get_session_array()                   
            ));       
        });  
        
        $app->post('/save', function () use ($app, $sec, $treatment) {
            $sec->check('properties');
            
            $id = $app->request->post('id');
            
            $property_id = $app->request->post('property_id');            
            $property_name = $app->request->post('property_name');                      
            $section = $app->request->post('section');                                  
            $sub_section = $app->request->post('sub_section');                          
            $auto_display = $app->request->post('auto_display');                        
            $active = $app->request->post('active');                
            $html_type = $app->request->post('html_type');                              
                                 
            $pos_label_top = $app->request->post('pos_label_top');                      
            $pos_label_left = $app->request->post('pos_label_left');                    
            $label_font = $app->request->post('label_font');                            
            $label_font_size = $app->request->post('label_font_size');                  
            $label_font_weight = $app->request->post('label_font_weight');              
            $pos_input_top = $app->request->post('pos_input_top');                      
            $pos_input_left = $app->request->post('pos_input_left');                    
            $input_width = $app->request->post('input_width');                          
            $input_height = $app->request->post('input_height');                        
                  
            $modify_options = $app->request->post('modify_options');           
            
            //$allPostVars = $app->request->post();        

            if (!$id) {
                if ($treatment->property_exists($property_id)) {
                    $app->flash('error', 'Error: ID already exists');
                    $app->redirect("/emrs/emrs/tools/treatment-items/$property_id");         
                }               
            } else {
                if ($id != $property_id) {
                   $app->flash('error', 'Error: ID change prohibited');
                   $app->redirect("/emrs/emrs/tools/treatment-items/$id");                         
                }                                
            }          

            if ($app->request->post('btn_save_property') == '1' ) {                
                $ret = $treatment->save_property($property_id, $property_name, $section, $sub_section, $auto_display, $active);
                if (!$ret) {
                    $app->flash('error', 'Error: Invalid input');
                    $app->redirect("/emrs/emrs/tools/treatment-items/$property_id");  
                }
                $ret = $treatment->save_property_html($property_id, $html_type, $pos_label_top, $pos_label_left, $label_font, $label_font_size, $label_font_weight, $pos_input_top, $pos_input_left, $input_width, $input_height, $modify_options);
                if (!$ret) {
                    $app->flash('error', 'Error: Invalid input');
                    $app->redirect("/emrs/emrs/tools/treatment-items/$property_id");  
                }                
            }
            
            if ($app->request->post('btn_save_options') == '1' ) {  
                $option_id = $app->request->post('option_id');                              
                $option_value = $app->request->post('option_value');                        
                $active = $app->request->post('option_active');
                
                $treatment->delete_options($id);

                for ($i = 0; $i < count($option_id); $i++) { 
                    if ($option_id[$i] == '') { continue; }
                    $treatment->add_options($option_id[$i], $id, $option_value[$i], $active[$i]);
                }              
            }            
            
            if ($app->request->post('btn_save_options2') == '1' ) {
                // property options_persons
                $user_group = $app->request->post('o_user_group');                            
                $user_role = $app->request->post('o_user_role');                              
                $user_level = $app->request->post('o_user_level');                            
                $o_active = $app->request->post('o_active'); 
                
                $ret = $treatment->save_options2($id, $user_group, $user_role, $user_level, $o_active);
                if (!$ret) {
                    $app->flash('error', 'Error: Invalid input');
                    $app->redirect("/emrs/emrs/tools/treatment-items/$property_id");  
                }               
            }
            
            //var_dump($allPostVars);
            $app->flash('info', "Property ID [$property_id] saved." );
            $app->redirect("/emrs/emrs/tools/treatment-items/$property_id"); 
        });            
        
        
        
    });
    
    $app->group('/meds', function () use ($app, $sec, $meds) {
        
        $app->get('/active', function () use ($app, $sec, $meds) {    
            $sec->check('meds');            
            $app->render('tools_meds.html', array(
                'title' => 'Meds',
                'med_id' => $meds->get_new_med_id(),
                'new_prep_id' => $meds->get_new_prep_id(),
                'meds_list' => $meds->list_meds(1),
                'session' => $sec->get_session_array()                   
            ));       
        });
        
        $app->get('/all', function () use ($app, $sec, $meds) {    
            $sec->check('meds');            
            $app->render('tools_meds.html', array(
                'title' => 'Meds',
                'med_id' => $meds->get_new_med_id(),
                'new_prep_id' => $meds->get_new_prep_id(),
                'meds_list' => $meds->list_meds(0),
                'session' => $sec->get_session_array()                   
            ));       
        });       
        
        $app->get('/:id', function ($id) use ($app, $sec, $meds) {    
            $sec->check('users');           
            $app->render('tools_meds.html', array(
                'title' => 'Meds',
                'med_id' => $id,
                'new_prep_id' => $meds->get_new_prep_id(),
                'meds_list' => $meds->list_meds(1),                
                'meds_details' => $meds->get_details($id),
                'meds_prep' => $meds->get_prep($id),                
                'session' => $sec->get_session_array()                  
            ));       
        });  
        
        $app->post('/save', function () use ($app, $sec, $meds) {
            $sec->check('meds');       
            $med_id = $app->request->post('med_id');                                    
            $med_name = $app->request->post('med_name');                                
            $active = $app->request->post('active');
            
            $prep_id = $app->request->post('prep_id');                              
            $prep_name = $app->request->post('prep_name');                              
            $prep_active = $app->request->post('prep_active'); 
            
            //$allPostVars = $app->request->post();
            //var_dump($allPostVars);             
            
            if ($app->request->post('btn_save_meds') == '1') {
                //if ($med_id == '') { $med_id = $meds->get_new_med_id(); }  
                if ($active == '') { $active  = '0'; } 
                
                if ($med_name == '' || $med_id == '') { 
                    $app->flash('error', 'Error: Invalid input');
                    $app->redirect("/emrs/emrs/tools/meds/active"); 
                } 
                
                $ret = $meds->save($med_id, $med_name, $active);
                if (!$ret) {
                    $app->flash('error', 'Error: Invalid input');
                    $app->redirect("/emrs/emrs/tools/meds/active"); 
                }  
            }           
            
            if ($app->request->post('btn_save_prep') == '1') {
                // this logic of saving is ok since we dont want to ophan the older entries
                for ($i = 0; $i < count($prep_id); $i++) { 
                    if ($prep_name[$i] == '')   { continue; }
                    //if ($prep_id[$i] == '')     { continue; }  
                    if (in_array($prep_id[$i], $prep_active)) { 
                        $a = '1';                       
                    } else {
                        $a = '0';
                    }
                    
                    $meds->prep_save($prep_id[$i], $med_id, $prep_name[$i], $a);
                }
            }
            
            $app->flash('info', "Information for id [$med_id] saved." );
            $app->redirect("/emrs/emrs/tools/meds/$med_id"); 
        });        
        
    
    });      
    
    
    
});

?>
<?php

$app->group('/patients', function () use ($app, $sec, $person, $presc, $treatment, $appointment, $lab, $misc) { 
    
    $app->get('/', function () use ($app, $sec) {
        $sec->check('patients');                 
        
        $app->render('patient_search.html', array(
            'title' => 'Patients',                 
            'session' => $sec->get_session_array()
        ));

    });   
    
    $app->get('/add', function () use ($app, $sec, $person) {
        $sec->check('patients');
        // generate the person_id agad so it will not cause prob on double form submission
        $pid =  $person->get_new_id();
        // save the id agad, to avoid collision
        //$person->prepare($pid);
        $app->render('patients.html', array(
            'title' => 'Patients',             
            'pid' => $pid,
            'show_patient_edit' => 1,           
            'person_header' => $person->get_person_header(),
            'patient_header' => $person->get_patient_header(), 
            'person_options' => $person->get_person_options(),
            'physicians' => $person->get_physicians(),            
            'session' => $sec->get_session_array()   
                
               
        ));
    });
    
    $app->post('/save', function () use ($app, $sec, $person) {
        $sec->check('patients');        
        $id = $app->request->post('pid');                              
        $fname = $app->request->post('fname');                                      
        $mname = $app->request->post('mname');                                      
        $lname = $app->request->post('lname');                                      
        $gender = $app->request->post('gender');                                    
        $birth_date = $app->request->post('birth_date');                            
        $civil_status = $app->request->post('civil_status');                        
        $address1 = $app->request->post('address1');                                
        $address2 = $app->request->post('address2');                                
        $city = $app->request->post('city');                                        
        $province = $app->request->post('province');                                
        $phone_number = $app->request->post('phone_number');                        
        $active = $app->request->post('active');         
                                   
        $dry_weight = $app->request->post('dry_weight');                            
        $physician_id = $app->request->post('physician_id');                        
        $hepa_status = $app->request->post('hepa_status'); 
        $first_dialysis = $app->request->post('first_dialysis'); 
        $diagnosis = $app->request->post('diagnosis'); 
        $blood_type = $app->request->post('blood_type');         
        $hemodialysis_orders = $app->request->post('hemodialysis_orders'); 

        
             
        if ($active == '') { $active  = '0'; }              
        
        $ret = $person->save($id, $fname, $mname, $lname, $gender, $birth_date, $civil_status, $address1, $address2, $city, $province, $phone_number, $active);
        if (!$ret) {
            $app->flash('error', 'Error: Invalid input');
            $app->redirect("/emrs/emrs/patients/edit/$id");        
        }
        
        $ret = $person->save_patient($id, $dry_weight, $physician_id, $hepa_status,$first_dialysis, $diagnosis, $blood_type, $hemodialysis_orders);
        if (!$ret) {
            $app->flash('error', 'Error: Invalid input');
            $app->redirect("/emrs/emrs/patients/edit/$id");          
        }          
   
        //$allPostVars = $app->request->post();        
        //var_dump($allPostVars);      

        $app->flash('info', "Information for $fname $lname saved.");
        $app->redirect("/emrs/emrs/patients/edit/$id");        

    }); 
 
    $app->post('/find', function () use ($app, $sec) {    
        $sec->check('patients');  
        $param       = $app->request->post('param');
        $gender      = $app->request->post('gender');
        $hepa_status = $app->request->post('hepa_status'); 
        $active      = $app->request->post('active'); 
        
        $tmp = explode(',', $param);
        if (count($tmp) > 1) {
            $query = trim($tmp[0]) . '-' . trim($tmp[1]);
            if (strlen($tmp[0]) == 0 && strlen($tmp[1]) == 0) {
                $query = "All";           
            }            
        } else {
            if (strlen($tmp[0]) == 0 ) {
                $query = "All";           
            } else {           
                $query = "$tmp[0]";            
            }
        }       
        
        $app->redirect("/emrs/emrs/patients/find/$query/$gender/$hepa_status/$active");        
     });
     
    $app->get('/find/:query/:gender/:hepa_status/:active', function ($query, $gender, $hepa_status, $active) use ($app, $sec, $person) {    
        $sec->check('patients');
        $app->flash('link', "/emrs/emrs/patients/find/$query/$gender/$hepa_status/$active");
        if ($gender == 'Both')     { $gender = ''; }
        if ($active == 'All')      { $active = ''; }
        if ($active == 'Active')   { $active = '1'; }
        if ($active == 'Inactive') { $active = '0'; }
        if ($hepa_status == 'All') { $hepa_status = ''; }    
        if ($query == 'All') { $query = ''; }        
        
        $tmp = explode('-', $query);
        if (count($tmp) > 1) {
            $param = trim($tmp[0]) . ',' . trim($tmp[1]);          
        } else {
            $param = "$tmp[0]";            
        }
        
        $result = $person->find($query, $gender, $hepa_status, $active);
        //var_dump($result);
        $app->render('patient_search.html', array(
            'title' => 'Patients',           
            'searching' => 1,
            'results' => $result,
            'param' => $param,
            'gender' => $gender,
            'active' => $active,
            'hepa_status' => $hepa_status,
            'session' => $sec->get_session_array()    
        ));      
     }); 
     
    $app->get('/:id', function ($id) use ($app, $sec) {      
        $app->redirect("/emrs/emrs/patients/view/$id");             
    });     
    
    $app->get('/edit/:id', function ($id) use ($app, $sec, $person) {        
        $sec->check('patients');
        $app->render('patients.html', array(
            'title' => 'Patient Edit',
            'pid' => $id,
            'show_patient_edit' => 1,            
            'person_header' => $person->get_person_header(),
            'patient_header' => $person->get_patient_header(),
            'person_values' => $person->get_person_values($id),
            'patient_values' => $person->get_patient_values($id),
            'person_options' => $person->get_person_options(),
            'physicians' => $person->get_physicians(),            
            'session' => $sec->get_session_array()    
        ));  
        
    });   
        
    $app->get('/treatment/:id', function ($id) use ($app, $sec, $person, $treatment) {        
         $sec->check('patients'); 
         $app->render('patients.html', array(
             'title' => 'Treatments',
             'pid' => $id, 
             'person_values' => $person->get_person_values($id),
             'show_treatment_history' => 1,                  
             'treatment_history' => $treatment->get_history($id),
             'session' => $sec->get_session_array()    
         )); 
     }); 
     
    $app->get('/appointment/:id', function ($id) use ($app, $sec, $person, $appointment) {        
         $sec->check('patients'); 
         $app->render('patients.html', array(
            'title' => 'Appointments',
            'pid' => $id,             
            'show_appointment_history' => 1,          
            'appointment_history' => $appointment->get_history($id), 
            'session' => $sec->get_session_array()    
         )); 
    });  
     
    $app->get('/lab-results/:id', function ($id) use ($app, $sec) {        
        $sec->check('patients'); 
        $app->redirect("/emrs/emrs/lab-results/view/all/$id");    
    }); 
     
    $app->post('/upload/:type', function ($type) use ($app, $sec, $misc) {
        $sec->check('patients');        
        $id = $app->request->post('pid'); 
        
        // this is the direct link. ex: h:\xmpp\htdocs
        $uploadfile = $misc->get_uploads_dir($type) . $id;   
        
        if (!is_uploaded_file($_FILES[$type]['tmp_name'])) {
            $app->redirect("/emrs/emrs/patients/view/$id");           
        }
        
        move_uploaded_file($_FILES[$type]['tmp_name'], $uploadfile);    
        $app->redirect("/emrs/emrs/patients/view/$id");
             
    });
    
    $app->get('/view/:id', function ($id) use ($app, $sec, $person, $treatment, $lab, $misc, $presc) {        
        $sec->check('patients');
        $token = md5(md5($id) . 'emrs' . md5($id));
        
        $has_profile_pic = 0; 
        $has_scc_id = 0; 
        $has_pwd_id = 0; 
        
        $img_profile_pic = $misc->get_uploads_dir('profile_pic') . $id;         
        if (file_exists($img_profile_pic)) {
            $has_profile_pic = 1;           
        }
        
        $img_scc_id = $misc->get_uploads_dir('scc_id') . $id;         
        if (file_exists($img_scc_id)) {
            $has_scc_id = 1;           
        }
        
        $img_pwd_id = $misc->get_uploads_dir('pwd_id') . $id;         
        if (file_exists($img_pwd_id)) {
            $has_pwd_id = 1;           
        }        
        
        $app->render('patient_profile.html', array(            
            'pid' => $id,
            'entries' => $presc->get_entries($presc->get_latest($id)),
            'img_profile_pic' => '/emrs/uploads/profile_pic/' . $id,  
            'img_scc_id' => '/emrs/uploads/scc_id/' . $id,  
            'img_pwd_id' => '/emrs/uploads/pwd_id/' . $id, 
            'img_blank' => '/emrs/pub/img/no_image.gif', 
            'has_profile_pic' => $has_profile_pic, 
            'has_scc_id' => $has_scc_id,
            'has_pwd_id' => $has_pwd_id,            
            'date_today' => date("M d, Y"),
            'hp1' => $lab->get_latest_data($id, '69'),
            'hp2' => $lab->get_latest_data($id, '70'),
            'hp3' => $lab->get_latest_data($id, '71'),
            'ftd' => $treatment->get_first_date($id),
            'duration' => $treatment->get_latest_data($id, '2'),
            'dialyzer' => $treatment->get_latest_data($id, '9'),
            'uf_goal' => $treatment->get_latest_data($id, '6'),
            'token' => $token,
            'info' => $person->get_info($id),
            'age' => $person->get_age($id),
            'session' => $sec->get_session_array()    
        ));        
    });  
    
    $app->get('/remove/:type/:id/:token', function ($type, $id, $token) use ($app, $sec, $misc) {        
        $sec->check('patients');        
        $token2 = md5(md5($id) . 'emrs' . md5($id)); 
        $img = $misc->get_uploads_dir($type) . $id; 
        if ($token2 == $token) {
            if (file_exists($img)) {
                unlink($img);          
            }             
        }        
        $app->redirect("/emrs/emrs/patients/view/$id");
    });    
     
    
});

?>

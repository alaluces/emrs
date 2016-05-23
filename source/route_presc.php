<?php

$app->group('/prescriptions', function () use ($app, $sec, $presc, $meds, $person) { 
    
    $app->get('/delete-ask/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc, $meds, $person) {   
         
         $sec->check('presc');   
         $app->render('prescriptions.html', array(
            'title' => 'Medications',
            'delete_ask' => 1,   
            'pid' => $pid,
            'presc_id' => $presc_id,                 
            'physician_info' => $person->get_pro_info($person->get_physician_id($presc_id)),
            'entries' => $presc->get_entries($presc_id),            
            'physicians' => $person->get_physicians(),
            'person' => $person->get_info($pid),
            'age' => $person->get_age($pid),
            'meds' => $meds->get_meds(),
            'prescription_list' => $presc->get_list($pid), 
            'session' => $sec->get_session_array()                   
         ));    
     });  
     
    $app->get('/delete/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc) {         
         $sec->check('presc');   
         $entry_time = date("His"); 
         $entry_date = date("Ymd"); 
         $presc->log($presc_id, $entry_date, $entry_time, $_SESSION['person_id'], 'DELETED', '');
         $app->flash('info', 'Deleted');
         $app->redirect("/emrs/emrs/medications/$pid");      
     });
     
    $app->get('/view/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc, $meds, $person) {    
        $sec->check('presc');            
        $app->render('prescriptions.html', array(
            'title' => 'Medications',             
            'pid' => $pid,
            'presc_id' => $presc_id, 
            'person' => $person->get_info($pid),
            'entries' => $presc->get_entries($presc_id),
            'view_mode' => '1',        
            'meds' => $meds->get_meds(),
            'prescription_list' => $presc->get_list($pid), 
            'session' => $sec->get_session_array()                   
        ));       
    });    
    
    $app->get('/print/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc, $meds, $person) {    
        $sec->check('presc'); 
        
        $count_entries = count($presc->get_entries($presc_id));
        
        //echo ceil($count_entries / 8);
        //var_dump($presc->get_entries($presc_id));
        //die();
                
        $app->render('prescriptions_print.html', array(
            'title' => 'Medications',             
            'pid' => $pid,
            'presc_id' => $presc_id,
            'physician_info' => $person->get_pro_info($person->get_physician_id($presc_id)),
            'entries' => $presc->get_entries($presc_id),
            'count_entries' => $count_entries,
            'total_pages' => ceil($count_entries / 9),
            'print_mode' => '1',
            'physicians' => $person->get_physicians(),
            'person' => $person->get_info($pid),
            'age' => $person->get_age($pid),
            'meds' => $meds->get_meds(),
            'prescription_list' => $presc->get_list($pid), 
            'session' => $sec->get_session_array()                   
        ));       
    });     
   
    $app->get('/edit/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc, $meds, $person) {    
        $sec->check('presc');            
        $app->render('prescriptions.html', array(
            'title' => 'Medications', 
            'person' => $person->get_info($pid),            
            'pid' => $pid,
            'presc_id' => $presc_id,
            'presc_id_latest' => $presc->get_latest($pid),
            'physician_info' => $person->get_pro_info($person->get_physician_id($presc_id)),
            'entries' => $presc->get_entries($presc_id),
            'edit_mode' => '1',
            'physicians' => $person->get_physicians(),
            'meds' => $meds->get_meds(),
            'prescription_list' => $presc->get_list($pid), 
            'session' => $sec->get_session_array()                   
        ));       
    }); 
    
    $app->post('/save', function () use ($app, $sec, $presc) {    
        $sec->check('presc');   
        
        //$allPostVars = $app->request->post();        
        $id = $app->request->post('pid');                              
        $physician_id = $app->request->post('physician_id');          
        $presc_id = $app->request->post('presc_id');       
        $med_id = $app->request->post('med_id');  
        $prep_id = $app->request->post('prep_id');
        $chk_med_ids = $app->request->post('chk_med_ids');
        $qty = $app->request->post('qty');                                          
        $amt = $app->request->post('amt');                                          
        $frequency = $app->request->post('frequency');                              
        $duration = $app->request->post('duration');                                
        $notes = $app->request->post('notes'); 
        
        $entry_time = date("His"); 
        $entry_date = date("Ymd");  
        
       
        if ($app->request->post('btn_duplicate') == '1' ) {    
            $presc_id = $presc->get_new_id();
        }
        
        //var_dump($allPostVars);

        $presc->save($presc_id, $id, $physician_id);
        $presc->delete_entries($presc_id);         
        
        for ($i = 0; $i < count($med_id); $i++) {
            // "$med_id[$i]|$prep_id[$i]" - they want duplicate medications
            if (!in_array("$med_id[$i]|$prep_id[$i]", $chk_med_ids)) { continue; }
            //if ($presc->has_duplicate_entry($presc_id, $med_id, $prep_id)) { continue; }            
            $presc->add_entry($presc_id, $med_id[$i], $prep_id[$i], $qty[$i], $amt[$i], $frequency[$i], $duration[$i], $notes[$i]);
        }        
        
        $presc->log($presc_id, $entry_date, $entry_time, $_SESSION['person_id'], 'UPDATE', '');
        
        if ($app->request->post('btn_save') == '1' ) {   
            $app->flash('info', "Information saved." );
            //$app->redirect("/emrs/emrs/prescriptions/edit/$id/$presc_id"); 
            $app->redirect("/emrs/emrs/medications/$id");  
        }
        
        if ($app->request->post('btn_duplicate') == '1' ) {    
            $app->flash('info', "Duplicate success" );
            //$app->redirect("/emrs/emrs/prescriptions/edit/$id/$presc_id");   
            $app->redirect("/emrs/emrs/medications/$id");  
        }     
      
    });  
    
    $app->post('/add-to-list/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc) {    
        $sec->check('presc');   
        $flash = $_SESSION['slim.flash'];
            
        $med_id = $app->request->post('med_id');   
        $prep_id = $app->request->post('prep_id');    
        $qty = $app->request->post('qty');                                          
        $amt = $app->request->post('amt');                                          
        $frequency = $app->request->post('frequency');                              
        $duration = $app->request->post('duration');                                
        $notes = $app->request->post('notes'); 
        
        $entry_time = date("His"); 
        $entry_date = date("Ymd");
        
        if ($amt == '' || $frequency == '') {    
            $app->flash('error', "Invalid input" );
            $app->redirect($flash['link']);        
        }
        
        if ($presc->has_duplicate_meds($presc_id, $med_id, $prep_id)) { 
            $app->flash('error', "Medication already exists on the list" ); 
            $app->redirect($flash['link']);  
        }         
        
        $presc->add_entry($presc_id, $med_id, $prep_id, $qty, $amt, $frequency, $duration, $notes);
        $presc->log($presc_id, $entry_date, $entry_time, $_SESSION['person_id'], 'UPDATE', '');        
        
        $app->flash('info', "Item added." );
        $app->redirect("/emrs/emrs/prescriptions/edit/$pid/$presc_id");       
      
    });     
    
    $app->get('/add/:pid', function ($pid) use ($app, $sec, $presc) {    
        $sec->check('presc');   
        $presc_id = $presc->get_new_id();
        $presc->save($presc_id, $pid, '');
        $app->redirect("/emrs/emrs/prescriptions/edit/$pid/$presc_id");       
    });  
    
     
    
   
    
    

    
    
});

?>
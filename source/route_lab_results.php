<?php

$app->group('/lab-results', function () use ($app, $sec, $lab, $person, $misc) {
    
    $app->get('/view/:cid/:id', function ($cid, $id) use ($app, $sec, $lab, $person) {    
        $sec->check('labs');            
        $app->render('lab_results.html', array(
            'title' => 'Lab Results', 
            'pid' => $id, 
            'cid' => $cid,
            'person' => $person->get_info($id),
            'lab_menu_list' => $lab->get_category_menu_list(),   
            'lab_results' => $lab->get_all_results($id),
            'lab_properties' => $lab->get_property_list(),           
            'session' => $sec->get_session_array()                   
        ));       
    }); 
    
    $app->get('/edit/:cid/:id/:date', function ($cid, $id, $date) use ($app, $sec, $lab, $person) {    
        $sec->check('labs');            
        $app->render('lab_results.html', array(
            'title' => 'Lab Results', 
            'edit_mode' => '1',
            'pid' => $id,
            'cid' => $cid,
            'person' => $person->get_info($id),
            'old_date' => $date,
            'lab_menu_list' => $lab->get_category_menu_list(),   
            'lab_results' => $lab->get_all_results($id),
            'lab_properties' => $lab->get_property_list(),  
            'session' => $sec->get_session_array()                   
        ));       
    }); 
    
    $app->get('/delete-ask/:cid/:id/:date', function ($cid, $id, $date) use ($app, $sec, $lab, $person) {    
        $sec->check('labs');            
        $app->render('lab_results.html', array(
            'title' => 'Lab Results', 
            'delete_ask' => '1',
            'edit_mode' => '1',
            'pid' => $id,
            'cid' => $cid,
            'person' => $person->get_info($id),
            'old_date' => $date,
            'lab_menu_list' => $lab->get_category_menu_list(),   
            'lab_results' => $lab->get_all_results($id),
            'lab_properties' => $lab->get_property_list(),  
            'session' => $sec->get_session_array()                   
        ));       
    }); 
    
    $app->get('/delete/:cid/:id/:date', function ($cid, $id, $date) use ($app, $sec, $lab, $misc) {    
        $sec->check('labs'); 
        
        $eid = $lab->get_eid($id, $cid, $date);
        if ($eid) {             
            $lab->delete_entries($eid); 
            $lab->delete_logs($eid);            
             
            $img_path = $misc->get_uploads_dir('labs') . "$date-$cid-$id";            
            if (file_exists($img_path)) {
                unlink($img_path);      
            }                        
            
            $app->flash('info', "Entry deleted");
            $app->redirect("/emrs/emrs/lab-results/view/$cid/$id");               
        } else {
            $app->flash('error', "Entry not found");
            $app->redirect("/emrs/emrs/lab-results/view/$cid/$id");           
        }         
    });     
    
    $app->post('/save', function () use ($app, $sec, $lab, $misc) {
        $sec->check('labs');  
        
        $allPostVars = $app->request->post();
        $id        = $app->request->post('pid');
        $cid       = $app->request->post('cid'); 
        $old_date  = $app->request->post('old_date'); 
        $new_date  = $app->request->post('new_date');  
        $entry_time = date("His"); 
        
        if ($new_date == '') {
            $new_date = date("Ymd");                      
        }  
        
        // if date has changed on edit, check new date if it exists
        if ($old_date != $new_date) {
            if ($lab->get_eid($id, $cid, $new_date)) {
                $app->flash('error', "Date already exists");
                $app->redirect("/emrs/emrs/lab-results/view/$cid/$id");                 
            } else {
                $old_eid = $lab->get_eid($id, $cid, $old_date);
                $lab->delete_entries($old_eid); 
                $lab->delete_logs($old_eid);                 
            }           
        }  
        
        // new entry
        if ($old_date == '') {            
            // error if selected date already exists. you should edit the existing record 
            if ($lab->get_eid($id, $cid, $new_date)) {
                $app->flash('error', "Date already exists");
                $app->redirect("/emrs/emrs/lab-results/view/$cid/$id");                 
            }                     
        }  
        
        $eid = $lab->get_eid($id, $cid, $new_date);
        if ($eid) {             
            $lab->delete_entries($eid); 
            $lab->delete_logs($eid); 
        } else {                     
            $eid = $lab->get_new_eid();
            $lab->new_entry($eid, $id, $cid);
        }  
            
        // do not save values if its on the del_list
        $del_list = $lab->get_delete_list($cid);       
        // format the array as stack this time
        $del_list2 = array();        
        foreach ($del_list as $row) {           
            array_push($del_list2, $row[0] );
        }
        
        foreach ($allPostVars as $key => $val) {           
            if (!in_array($key, $del_list2)) { continue; }
            //echo "$key: $val deleted<br>";
            unset($allPostVars[$key]);
        }
        
        // Upload scanned lab results
        // this is the direct link. ex: h:\xmpp\htdocs
        if (in_array($cid, array('9','10'))) {        
            $uploadfile = $misc->get_uploads_dir('labs') . "$new_date-$cid-$id";            
            if (is_uploaded_file($_FILES['lab_pic']['tmp_name'])) {
                move_uploaded_file($_FILES['lab_pic']['tmp_name'], $uploadfile);       
            }         
        }
        //var_dump($allPostVars);           
        
        $lab->save($allPostVars, $eid);
        $lab->log($eid, $new_date, $entry_time, $_SESSION['person_id'], 'UPDATE', '');             
                
        $app->flash('info', "Information saved.");
        $app->redirect("/emrs/emrs/lab-results/view/$cid/$id");        
        
    });  
    
    
    
    
    
    
});

?>
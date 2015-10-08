<?php

$app->group('/treatments', function () use ($app, $sec, $person, $treatment, $appointment) {
        
    // get the date and person id so we can get the treatment id and current version
    $app->get('/:entry_date/:id', function ($entry_date, $id) use ($app, $sec, $treatment) {
        $sec->check('treatments');    
        // get treatment id and redirect again, or add new treatment id if not yet existing
        
        $entry_time = date("His");
        $tid = $treatment->exists($id, $entry_date);
        if ($tid) {
            $vid = $treatment->get_vid($tid);
            $app->redirect("/emrs/emrs/treatments/$entry_date/$id/$tid/$vid");
        } else {
            // if not found and date is today, create it, else error not found
            if ($entry_date == date("Ymd")) {
                $tid = $treatment->get_new_tid();                
                $treatment->add_new($tid, $id);
                $treatment->log($tid, '1', $entry_date, $entry_time, 'CREATED', '');  
                $app->redirect("/emrs/emrs/treatments/$entry_date/$id/$tid/1");                  
            } else {
                $app->flash('error', 'Error: Treatment number not found');
                $app->redirect("/emrs/emrs/appointments");               
            }          
        }       
    });      
      
    $app->get('/:entry_date/:id/:tid/:vid', function ($entry_date, $id, $tid, $vid) use ($app, $sec, $person, $treatment, $appointment) {
        $sec->check('treatments');    

        // get duration for initial values of monitoring sheet, if not found, default value is 4
        if ($vid == '1') {
            $duration = '4';  
            $interval = '15';
        } else {
            $duration = $treatment->get_data($tid, '2');
            if (!$duration) {
                $duration = '4';
            }  
            $interval = $treatment->get_data($tid, '26');
            if (!$interval) {
                $interval = '15';
            }            
        }
        $time_arrived  = $appointment->get_status_time($id, $entry_date, 'WAITING');
        $time_started  = $appointment->get_status_time($id, $entry_date, 'ONGOING');
        $time_finished = $treatment->get_status_time($id, $entry_date, 'CLOSED');

        $app->render('treatment_form.html', array(
            'title' => 'Treatment',
            'pid' => $id,
            'tid' => $tid,
            'vid' => $vid,
            'entry_date' => $entry_date,
            'entry_date2' => strtotime($entry_date),
            'age' => $person->get_age($id),            
            'prev_weight' => $treatment->get_prev_data($id, $tid, '7'),
            'prev_order' => $treatment->get_prev_data($id, $tid, '20'),
            'entry_status' => $treatment->get_status($id, $tid),
            'duration' => $duration,
            'time_started' => strtotime("$time_started"),
            'time_arrived' => strtotime("$time_arrived"),
            'time_finished' => strtotime("$time_finished"),
            'intervals' => $treatment->generate_time_intervals($duration, $interval,$time_started),
            'ms' => $treatment->get_monitoring_sheet($tid),                
            'person' => $person->get_info($id),    
            'max_vid' => $treatment->get_vid($tid),            
            'fields_main' => $treatment->get_info($tid, $vid),
            'misc_html_header' => $treatment->get_misc_html('header'),
            'misc_html' => $treatment->get_misc_html('treatment'),
            'treatment_history' => $treatment->get_history($id),
            'options_persons' => $treatment->get_options_persons(),
            'options' => $treatment->get_options(),
            'session' => $sec->get_session_array()    
        ));
    });      
    
    
    $app->post('/save', function () use ($app, $sec, $treatment, $appointment) {    
        $sec->check('treatments');    
        $entry_date = date("Ymd");
        $entry_time = date("His");
        $allPostVars = $app->request->post();
        $pid = $app->request->post('pid');
        $tid = $app->request->post('tid');
        $vid = $app->request->post('vid');       
        
        $aid        = $appointment->get_id($pid, $entry_date);
        $set_number = $appointment->get_set_number($aid, $entry_date);        
        
        //var_dump($allPostVars);
        
        if ($app->request->post('btn_start_now') == '1' ) {
            
            $treatment->log($tid, $vid, $entry_date, $entry_time, 'START_TREATMENT', '');
            $appointment->update($aid, $entry_date, $entry_time, $set_number, 'ONGOING');
            
        } else {
            
            $vid++;

            // delete all entries exept last 3
            for ($i = 1; $i <= $vid - 4; $i++) {
                $treatment->delete_entries($tid, $i);                
            }            

            // Loop thru each post variables
            // legend for treatment form post variables
            // property_id, value type(0=values, 1=password auth), index of values)
            foreach($allPostVars as $key => $val) {
                //echo "[$key] [$val]<br>";
                $arrtmp = explode(',', $key);
                if (count($arrtmp) <= 1) {
                    continue;
                }
                if ($arrtmp[2] == '1') {
                    continue;
                }

                // 20150506 verify who primed cannulated etc thru password
                // if select_persons aka need to authenticate pw
                if ($arrtmp[1] == '1') {                   
                    $pw = $allPostVars[$arrtmp[0] . ',1,1'];                    
                    $t  = explode(',', $val);
                    if (count($t) <= 1) { 
                        if (!$sec->authenticate_by_id($val, $pw)) {
                            continue;
                        }
                    } else {
                        if ($t[0] == 'SET') { // dont enter the pw if data is already set
                            $val = $t[1];
                        } else {
                            continue;
                        } 
                    }                    
                }        

                if (is_array($val)) {
                    $val = implode(',', $val);                  
                } 
                    
                $treatment->add_entries($tid, $vid, $arrtmp[0], $val);                                
            } 
            
            if ($app->request->post('btn_update') == '1' ) {
                $treatment->log($tid, $vid, $entry_date, $entry_time, 'UPDATE_TREATMENT', '');  
            }
                
            if ($app->request->post('btn_done') == '1' ) {                           
                $treatment->log($tid, $vid, $entry_date, $entry_time, 'CLOSED', '');
                $appointment->update($aid, $entry_date, $entry_time, $set_number, 'DONE'); 
            }                     

            $ms_time     = $app->request->post('ms_time');
            $ms_bp1      = $app->request->post('ms_bp1');
            $ms_bp2      = $app->request->post('ms_bp2');
            $ms_ap       = $app->request->post('ms_ap');
            $ms_vp       = $app->request->post('ms_vp');
            $ms_qb       = $app->request->post('ms_qb');
            $ms_tmp      = $app->request->post('ms_tmp');
            $ms_ufvol    = $app->request->post('ms_ufvol');
            $ms_comments = $app->request->post('ms_comments');           

            $treatment->delete_monitoring_sheet($tid);

            for ($i = 0; $i < count($ms_time); $i++) {                
                $treatment->save_monitoring_sheet($tid, $ms_time[$i], $ms_bp1[$i], $ms_bp2[$i], $ms_ap[$i], $ms_vp[$i], $ms_qb[$i], $ms_tmp[$i], $ms_ufvol[$i], $ms_comments[$i]);                
            }            
        }        
       
        $app->redirect("/emrs/emrs/treatments/$entry_date/$pid/$tid/$vid");
       
     });        
    
}); 

?>
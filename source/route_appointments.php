<?php

$app->group('/appointments', function () use ($app, $sec, $appointment, $person, $treatment) {
    
    $app->get('/a', function () use ($app, $sec, $appointment) {
        var_dump($sec->get_settings());
    });    
    
    // view the appointment for todays date
    $app->get('/', function () use ($app, $sec, $appointment) {
        $sec->check('appointments');  
        $entry_date = date("Ymd");
        $readable_date = date("m/d/Y", strtotime($entry_date));
        
        //var_dump($appointment->get_list($entry_date, ''));        
        $app->render('appointments.html', array(
            'title' => 'Appointments',
            'settings' => $sec->get_settings(),
            'today_readable_date' => $readable_date,
            'today' => $entry_date,
            'readable_date' => $readable_date,
            'entry_date' => $entry_date,           
            'today_set1' => $appointment->get_list($entry_date, '1'),
            'today_set2' => $appointment->get_list($entry_date, '2'),
            'today_set3' => $appointment->get_list($entry_date, '3'),            
            'set1' => $appointment->get_list($entry_date, '1'),
            'set2' => $appointment->get_list($entry_date, '2'),
            'set3' => $appointment->get_list($entry_date, '3'),
            'session' => $sec->get_session_array()
        ));
    }); 
    
    // view appointment on specified date
    $app->get('/view/:entry_date', function ($entry_date) use ($app, $sec, $appointment, $person) {        
        $sec->check('appointments'); 
        $today = date("Ymd");
        // from http://stackoverflow.com/questions/2487921/convert-date-format-yyyy-mm-dd-dd-mm-yyyy
        $readable_date = date("m/d/Y", strtotime($entry_date));
        
        $app->render('appointments.html', array(
            'title' => 'Schedule',
            'settings' => $sec->get_settings(),
            'today_readable_date' => date("m/d/Y"),
            'today' => $today,
            'readable_date' => $readable_date,
            'entry_date' => $entry_date, //for the main calendar            
            'today_set1' => $appointment->get_list($today, '1'),
            'today_set2' => $appointment->get_list($today, '2'),
            'today_set3' => $appointment->get_list($today, '3'),
            'set1' => $appointment->get_list($entry_date, '1'),
            'set2' => $appointment->get_list($entry_date, '2'),
            'set3' => $appointment->get_list($entry_date, '3'),
            'session' => $sec->get_session_array()
        ));
        
     });    
    
    // set appointment route   
    // 20150217 now uses $flash = $_SESSION['slim.flash'];
    // so it will remember the page 
    // 20150305 appointment logic corrections 
    // 20150306 reservation time corrections  
    $app->post('/set/:id', function ($id) use ($app, $sec, $appointment, $treatment, $person) {    
        $sec->check('appointments'); 
        $entry_date  = $app->request->post('entry_date');
        $set_number  = $app->request->post('set_number');
        $entry_time  = date("His");
        $date_now    = date("Ymd");
        $flash       = $_SESSION['slim.flash'];
        $person_info = $person->get_info($id);
        $settings    = $sec->get_settings();
        
        if (!isset($flash['link'])) {
            $flash['link'] = '/emrs/emrs/patients';
        }
        
        if ($entry_date == '') { $entry_date = $date_now; } 
        
        if ($set_number == '') {
            $app->flash('error', 'Error: Invalid set number');
            //$app->redirect("/emrs/emrs/patients/$id"); 
            $app->redirect($flash['link']);            
        }   
        
        // check for available slots depending on hepatitis status
        $hepa_status = $person_info['hepa_status'];
        $appointment_count = $appointment->get_count($entry_date, $hepa_status, $set_number);
        switch ($hepa_status) {
            case 'Negative' : 
                if ($appointment_count >= $settings[appointment_slots_negative]) {
                    $app->flash('error', 'Error: No more slot available for this set');
                    $app->redirect($flash['link']);        
                } 
                break;
            case 'Hepatitis B' :
                if ($appointment_count >= $settings[appointment_slots_hepa_b]) {
                    $app->flash('error', 'Error: No more slot available for this set');
                    $app->redirect($flash['link']);         
                } 
                break;               
            case 'Hepatitis C' :
                if ($appointment_count >= $settings[appointment_slots_hepa_c]) {
                    $app->flash("error", "Error: No more slot available for this set");
                    $app->redirect($flash['link']);          
                } 
                break;                
        }   
   
        if ($appointment->exists($id, $entry_date)) {
            $aid = $appointment->get_id($id, $entry_date);
            $s = $appointment->get_status($aid, $entry_date);
            if ($s == 'RESERVED' || $s == 'WAITING' || $s == 'ONGOING' || $s == 'DONE') {           
                $app->flash('error', 'Error: Appointment already exists');
                $app->redirect($flash['link']);
            }
        } else {
            $aid = $appointment->get_new_id();
            $appointment->add($aid, $id);
        }       
      // /* 
        if ($app->request->post('add')) {
            if ($entry_date == '') {
                $app->flash('error', 'Error: Invalid Input');
                $app->redirect($flash['link']);             
            }           
            $appointment->update($aid, $entry_date, $entry_time, $set_number, 'RESERVED');            
        }
        
        if ($app->request->post('add_today')) {              
            $appointment->update($aid, $entry_date, $entry_time, $set_number, 'WAITING');             
        }
        
        $app->flash('info', 'Appointment added');
        //$app->redirect("/emrs/emrs/appointments"); 
        $app->redirect($flash['link']);
      // */

    });    
    
    // 20150626 appointment logic corrections
    // should be 1 aid per date
    $app->get('/add/:set_number/:pid', function ($set_number, $pid) use ($app, $sec, $appointment) {
        
        $sec->check('appointments');        
        $entry_time = date("His");
        $entry_date = date("Ymd");

        if ($appointment->exists($pid, $entry_date)) {
            $aid = $appointment->get_id($pid, $entry_date);            
            $s = $appointment->get_status($aid, $entry_date);
            if ($s == 'WAITING' || $s == 'ONGOING' || $s == 'DONE') {        
                $app->flash('error', 'Error: Appointment already exists');   
                $app->redirect("/emrs/emrs/appointments");      
            }
        } else {
            $aid = $appointment->get_new_id();
            $appointment->add($aid, $pid);
        }         
                   
        $appointment->update($aid, $entry_date, $entry_time, $set_number, 'WAITING');
        $app->redirect("/emrs/emrs/appointments");        
     });    
     
    $app->get('/remove/:entry_date/:aid', function ($entry_date, $aid) use ($app, $sec, $appointment) {        
        $sec->check('appointments');         
        $entry_time = date("His"); 
        
        $set_number = $appointment->get_set_number($aid, $entry_date);
        $appointment->update($aid, $entry_date, $entry_time, $set_number, 'REMOVED');     
        $app->redirect("/emrs/emrs/appointments");   
        
     });    
     
    $app->get('/cancel/:entry_date/:aid', function ($entry_date, $aid) use ($app, $sec, $appointment) {        
        $sec->check('appointments');       
        $entry_time = date("His"); 
        
        $set_number = $appointment->get_set_number($aid, $entry_date);
        $appointment->update($aid, $entry_date, $entry_time, $set_number, 'CANCELLED');     
        $app->redirect("/emrs/emrs/appointments");   
        
     }); 
     
    $app->get('/cancel-ask/:entry_date/:aid/:pid', function ($entry_date, $aid, $pid) use ($app, $sec, $appointment, $person) {        
        $sec->check('appointments');  
        $entry_date = date("Ymd");
        $readable_date = date("m/d/Y", strtotime($entry_date));
        
              
        $app->render('appointments.html', array(
            'title' => 'Appointments',
            'aid' => $aid,
            'full_name' => $person->get_fullname($pid),
            'cancel_ask' => 1,
            'today_readable_date' => $readable_date,
            'today' => $entry_date,
            'readable_date' => $readable_date,
            'entry_date' => $entry_date,           
            'today_set1' => $appointment->get_list($entry_date, '1'),
            'today_set2' => $appointment->get_list($entry_date, '2'),
            'today_set3' => $appointment->get_list($entry_date, '3'),            
            'set1' => $appointment->get_list($entry_date, '1'),
            'set2' => $appointment->get_list($entry_date, '2'),
            'set3' => $appointment->get_list($entry_date, '3'),
            'session' => $sec->get_session_array()
        ));        
        
     });      
   
    
});

?>
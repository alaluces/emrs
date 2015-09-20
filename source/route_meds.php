<?php

$app->group('/medications', function () use ($app, $sec, $presc, $meds, $person) {
    
    $app->get('/:pid', function ($pid) use ($app, $sec, $presc, $person) {        
         $sec->check('meds');   
         $app->render('medications.html', array(
            'title' => 'Medications',
            'pid' => $pid,  
            'person' => $person->get_info($pid),
            'prescription_list' => $presc->get_list($pid), 
            'entries' => $presc->get_entries($presc->get_latest($pid)), 
            'session' => $sec->get_session_array()    
         ));    
     });  
     
    $app->get('/:pid/:presc_id', function ($pid, $presc_id) use ($app, $sec, $presc) {         
         $sec->check('meds');   
         $app->render('medications.html', array(
            'title' => 'Medications',
            'pid' => $pid,  
            'presc_id' => $presc_id,
            'prescription_list' => $presc->get_list($pid),
            'entries' => $presc->get_entries($presc_id),
            'session' => $sec->get_session_array()    
         ));    
     }); 
     
    $app->post('/find', function () use ($app, $sec) {    
        $sec->check('meds');  
        $med_name = $app->request->post('med_name');
        $pid      = $app->request->post('pid');
        $presc_id = $app->request->post('presc_id');
      
        
        if ($med_name == '') {
            $app->flash('error', 'Error: Invalid input');
            $app->redirect("/emrs/emrs/prescriptions/edit/$pid/$presc_id");
        }        
        
        $app->redirect("/emrs/emrs/medications/find/$med_name/$pid/$presc_id");
        
     });     
     
    $app->get('/find/:med_name/:pid/:presc_id', function ($med_name, $pid, $presc_id) use ($app, $sec, $meds, $presc, $person) {    
        $sec->check('meds'); 
        $app->flash('link', "/emrs/emrs/medications/find/$med_name/$pid/$presc_id");
       
        //var_dump($result);        
        $app->render('prescriptions.html', array(
            'title' => 'Medications',           
            'searching' => 1,
            'edit_mode' => 1,
            'query' => $med_name,
            'results' => $meds->find($med_name),
            'preps' => $meds->get_preps(),
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
    

});

?>
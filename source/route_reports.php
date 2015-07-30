<?php

$app->group('/reports', function () use ($app, $sec, $person, $lab) {
    
    $app->group('/laboratory', function () use ($app, $sec, $person, $lab) {
        
        $app->post('/', function () use ($app, $sec) {    
            $sec->check('labs'); 
            $person_id = $app->request->post('person_id');
            $prop_id   = $app->request->post('property_id');
            
            $a = implode(',', $person_id);
            $b = implode(',', $prop_id);
            $app->redirect("/emrs/emrs/reports/laboratory/$a/$b");
            //var_dump($a);
            // $a;
              

        });   
        
        $app->get('/', function () use ($app, $sec, $lab, $person) {    
            $sec->check('labs');            
            $app->render('reports_lab.html', array(
                'title' => 'Lab Report',
                'patients' => $person->get_patients(),      
                //'categories' => $lab->get_category_menu_list(),  
                'properties' => $lab->get_property_list(),
                'session' => $sec->get_session_array()                   
            ));       
        });   

        $app->get('/:pid/:cid', function ($pid, $cid) use ($app, $sec, $lab, $person) {    
            $sec->check('labs');            
            $app->render('reports_lab_output.html', array(
                'title' => 'Lab Report',
                'pid' => $pid,
                'cid' => $cid,
                'patients' => $person->get_patients(),      
                'categories' => $lab->get_category_menu_list(),
                'headers' => $lab->get_report_headers($pid, $cid),
                'values' => $lab->get_report_values($pid, $cid),
                'session' => $sec->get_session_array()                   
            ));       
        });         

        

    
    });      
    
    
    
});

?>
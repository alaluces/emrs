<?php

$app->group('/reports', function () use ($app, $sec, $person, $lab) {
    
    $app->group('/laboratory', function () use ($app, $sec, $person, $lab) {
        
        $app->post('/', function () use ($app, $sec) {    
            $sec->check('labs'); 
            $person_id   = $app->request->post('person_id');
            $prop_id     = $app->request->post('property_id');
            $report_type = $app->request->post('btn_rep');
            
            $a = implode(',', $person_id);
            $b = implode(',', $prop_id);
            $app->redirect("/emrs/emrs/reports/laboratory/$report_type/2015/$a/$b");
            //var_dump($report_type);
            //echo $report_type;
              

        });   
        
        $app->get('/', function () use ($app, $sec, $lab, $person) {    
            $sec->check('labs');            
            $app->render('reports_lab.html', array(
                'title' => 'Lab Report',
                'patients' => $person->get_patients(),      
                'categories' => $lab->get_category_menu_list(),  
                'properties' => $lab->get_property_list(),
                'session' => $sec->get_session_array()                   
            ));       
        });   

        $app->get('/:type/:year/:pid/:cid', function ($type, $year, $pid, $cid) use ($app, $sec, $lab, $person) {    
            $sec->check('labs'); 
            
            switch($type) {
                case '1':
                    $headers = $lab->get_report_lab_headers($year);
                    $values = $lab->get_report_lab_values($pid, $cid);
                    break;
                case '2':
                    $headers = $lab->get_report_headers($pid, $cid);
                    $values = $lab->get_report_values($pid, $cid);                    
                    break;               
            }
            
            $app->render('reports_lab_output.html', array(
                'title' => 'Lab Report',
                'pid' => $pid,
                'cid' => $cid,
                'type' => $type,
                'patients' => $person->get_patients(),      
                'categories' => $lab->get_category_menu_list(),
                'headers' => $headers,
                'values' => $values,
                'session' => $sec->get_session_array()                   
            ));       
        });         

        

    
    });      
    
    
    
});

?>
<?php

$app->group('/reports', function () use ($app, $sec, $person, $lab) {
    
    $app->group('/laboratory', function () use ($app, $sec, $person, $lab) {
        
        $app->post('/', function () use ($app, $sec) {    
            $sec->check('labs'); 
            $person_id   = $app->request->post('person_id');
            $category_id = $app->request->post('category_id');
            $app->redirect("/emrs/emrs/reports/laboratory/$person_id/$category_id");

        });   
        
        $app->get('/', function () use ($app, $sec, $lab, $person) {    
            $sec->check('labs');            
            $app->render('reports_lab.html', array(
                'title' => 'Lab Report',
                'patients' => $person->get_patients(),      
                'categories' => $lab->get_category_menu_list(),    
                'session' => $sec->get_session_array()                   
            ));       
        });   

        $app->get('/:pid/:cid', function ($pid, $cid) use ($app, $sec, $lab, $person) {    
            $sec->check('labs');            
            $app->render('reports_lab_output.html', array(
                'title' => 'Lab Report',
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
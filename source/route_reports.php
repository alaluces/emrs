<?php

$app->group('/reports', function () use ($app, $sec, $person, $lab) {
    
    $app->group('/laboratory', function () use ($app, $sec, $person, $lab) {
        
        $app->post('/', function () use ($app, $sec) {    
            $sec->check('labs'); 
            $person_id = $app->request->post('person_id');
            $prop_id   = $app->request->post('property_id');
            $year      = $app->request->post('year');
            $view_type = $app->request->post('btn_view');
            $dl_type   = $app->request->post('btn_dl');
            
            $a = implode(',', $person_id);
            $b = implode(',', $prop_id);
            
            if ($view_type) {
                $app->redirect("/emrs/emrs/reports/laboratory/$view_type/$year/$a/$b");    
            } else {
                $app->redirect("/emrs/emrs/reports/laboratory/download/$dl_type/$year/$a/$b");
            }
            
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
                    $header = $lab->get_report_lab_headers($year);
                    $values = $lab->get_report_lab_values($pid, $cid);
                    $html   = $lab->get_report_lab_html($header, $values);
                    break;
                case '2':
                    $header = $lab->get_report_latest_comparison_headers($pid, $cid);
                    $values = $lab->get_report_latest_comparison_values($pid, $cid);   
                    $html   = $lab->get_report_latest_comparison_html($header, $values);
                    break;               
            }

            $app->render('reports_lab_output.html', array(
                'title' => 'Lab Report',
                'body' => $html                 
            ));
       
        });        

        $app->get('/download/:type/:year/:pid/:cid', function ($type, $year, $pid, $cid) use ($app, $sec, $lab, $person) {    
            $sec->check('labs'); 
            
            switch($type) {
                case '1':
                    $header = $lab->get_report_lab_headers($year);
                    $values = $lab->get_report_lab_values($pid, $cid);
                    $html   = $lab->get_report_lab_html($header, $values);
                    $filename = $year . '_monthly_lab_report.xls';
                    break;
                case '2':
                    $header = $lab->get_report_latest_comparison_headers($pid, $cid);
                    $values = $lab->get_report_latest_comparison_values($pid, $cid);   
                    $html   = $lab->get_report_latest_comparison_html($header, $values);
                    $filename = 'comparison_report.xls';
                    break;               
            }

            header('Content-Type: application/octet-stream');  
            header('Content-type: application/vnd.ms-excel');
            header("Content-Disposition: attachment; filename=$filename");
            header('Content-Transfer-Encoding: binary');               

            echo $html;

        });          

    });      
    
    
    
});

?>
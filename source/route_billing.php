<?php

$app->group('/billing', function () use ($app, $sec, $billing, $person) { 
    
    $app->get('/:pid/:tid', function ($pid, $tid) use ($app, $sec, $billing, $person) {        
         $sec->check('billing');   
         $app->render('billing.html', array(
            'title' => 'Billing',
            'pid' => $pid,  
            'person' => $person->get_info($pid),
            'discounts' => $billing->get_discounts(), 
            //'billing_list' => $billing->get_list($pid), 
            'session' => $sec->get_session_array()    
         ));    
     });     
    
    
    
    
    
    
    
    
    
    
    
    
    

});

?>
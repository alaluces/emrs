<?php

$app->get('/', function () use ($app, $sec) {
    $sec->check('root');
    $app->redirect("/emrs/emrs/home"); 
 
});

$app->get('/login', function () use ($app) {    
    $app->render('login.html', array(
        'title' => 'Login'
    ));
});

$app->get('/logout', function () use ($app) {  
    session_destroy();
    $app->redirect("/emrs/emrs/login");      
});

$app->post('/login', function () use ($app, $sec) {
    $username    = $app->request->post('username');
    $password    = $app->request->post('password');
    
    if ($username == '' && $password == '') {
        $app->flash('error', 'Error: Invalid input');
        $app->redirect("/emrs/emrs/login");                   
    }
    
    if ($sec->login($username, $password)) {         
        $app->redirect("/emrs/emrs/home");        
    } else {
        //session_destroy();
        $app->flash('error', 'Error: Incorrect username or password');
        $app->redirect("/emrs/emrs/login");    
    }      
});  

$app->get('/home', function () use ($app, $sec) {    
    $sec->check('home');    
    $app->render('home.html', array(
        'title' => 'Home',      
        'session' => $sec->get_session_array()
    ));   
});

$app->get('/restricted', function () use ($app, $sec) {   
    $app->render('restricted.html', array(
        'title' => 'Restricted.html',      
        'session' => $sec->get_session_array()
    ));   
});


//=============================================================================
$app->get('/range/:start/:end', function ($start,$end) use ($app, $misc) {
    $misc->range($start, $end);
});

$app->get('/time', function () use ($misc) {
    $misc->generate_time_intervals(4, '090101');
});


//=============================================================================
 

?>
<?php

// Build the uploads directory
$dirs = array('profile_pic','pwd_id','scc_id','labs');
$misc->set_dirs($dirs);
$misc->build_uploads_dir();

?>
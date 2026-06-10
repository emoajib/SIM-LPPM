<?php

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
header('Content-Type: text/plain');
echo 'upload_max_filesize: '.ini_get('upload_max_filesize')."\n";
echo 'post_max_size: '.ini_get('post_max_size')."\n";
unlink(__FILE__); // Automatically delete this file after execution for security

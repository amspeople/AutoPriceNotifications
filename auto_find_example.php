<?php
include_once 'Autopiter.php';
include_once 'EMEX.php';


$autopiter = new Autopiter('your_login', 'your_password', 'your_email');
$autopiter->find('107906787', 2000);

$emex = new EMEX('your_login', 'k++your_password', 'your_email');
$emex->find('868114Y500', 2000);


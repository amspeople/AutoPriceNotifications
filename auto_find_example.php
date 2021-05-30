<?php
include_once 'Autopiter.php';
include_once 'EMEX.php';

/* ПОИСК НА AUTOPITER.RU */
// Параметр email - необязательный
$autopiter = new Autopiter('your_login', 'your_password', 'your_email');
// Если isDebug=true, то в окно браузера будет выводиться информация с результатами поиска
// Полезно прежде, чем добавлять задачу в Cron
$autopiter->isDebug=true;
$autopiter->find('107906787', 2800);

/* ПОИСК НА EMEX.RU */
$emex = new EMEX('your_login', 'k++your_password', 'your_email');
$emex->isDebug=true;
$emex->find('868114Y500', 2800);


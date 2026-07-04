<?php
require __DIR__ . '/../src/init.php';
require __DIR__ . '/../src/DB.php';
require __DIR__ . '/../src/Notification.php';
use App\Notification;

$count = Notification::sendPending(100);
echo "Sent: $count notifications\n";

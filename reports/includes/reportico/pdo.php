
<?php

foreach(PDO::getAvailableDrivers() as $driver)
    {
    echo $driver.'<br />';
    }

$db = new PDO("mysql:host=127.0.0.1; dbname=swfr_db",
     "tomagar", "FlyFisherman1");
var_dump($db);
$stmt = $db->query("show tables");
$res = $stmt->fetch( PDO::FETCH_BOTH );
var_dump($res);
//echo "Table contents: $rows.\n";
// use the connection here


// and now we're done; close it
$db = null;
?> 

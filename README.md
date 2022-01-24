# xdatatabase
PHP class that interacts with mysql database using PDO.

**BASIC USAGE**
````php
require_once('Database.class.php');

$db = new Database();

$db->base = "cms";
$db->query = "SELECT * FROM users WHERE id = ?";
$users = $db->select();
````

**RESULT**
````
[{"id":1,"name":"Luke","id":2,"name":"Nate"}]
````

------------------------------------------------------------------------------------------------------

**ENABLE DEBUG**
````php
$db = new Database(true);
````

------------------------------------------------------------------------------------------------------

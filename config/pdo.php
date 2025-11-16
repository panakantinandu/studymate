$host = getenv("DB_HOST");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

$dsn = "mysql:host=$host;dbname=$db;charset=utf8";

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

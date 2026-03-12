<?php
namespace App\Config;

use PDO;
use PDOException;
use App\Core\ErrorHandler;

class Database
{
     private static $pdo = null;

     public static function getConnection(): PDO
     {
          if (self::$pdo === null) {
               // Cargar .env para scripts CLI que no pasan por index.php
               if (!isset($_ENV['DB_HOST'])) {
                    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
                    $dotenv->load();
               }

               date_default_timezone_set('America/Santo_Domingo');
               $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
               $port = $_ENV['DB_PORT'] ?? '3306';
               $db = $_ENV['DB_DATABASE'] ?? 'hospitall';
               $user = $_ENV['DB_USERNAME'] ?? 'root';
               $pass = $_ENV['DB_PASSWORD'] ?? '';
               $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

               $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
               $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
               ];

               try {
                    self::$pdo = new PDO($dsn, $user, $pass, $options);
               } catch (PDOException $e) {
                    ErrorHandler::handle($e);
               }
          }
          return self::$pdo;
     }
}

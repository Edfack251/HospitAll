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
               // Configuración de la base de datos basada en readme.md
               date_default_timezone_set('America/Santo_Domingo');
               $host = '127.0.0.1';
               $db = 'hospitall';
               $user = 'root';
               $pass = '';
               $charset = 'utf8mb4';

               $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
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

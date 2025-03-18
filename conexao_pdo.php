<?php
$host = "localhost";
$user = "root";
$password = ""; // Senha vazia no XAMPP
$database = "estoque"; // Banco de dados correto

// Conexão com mysqli
$mysqli = mysqli_connect($host, $user, $password, $database);

if (mysqli_connect_errno()) {
    die("Falha na conexão: " . mysqli_connect_error());
}

// Classe wrapper para simular PDO com mysqli
class PDOWrapper {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function prepare($query) {
        $stmt = $this->mysqli->prepare($query);
        return new PDOStatementWrapper($stmt);
    }
    
    public function query($query) {
        $result = $this->mysqli->query($query);
        if (!$result) {
            return false;
        }
        return new PDOResultWrapper($result);
    }
    
    public function setAttribute($attribute, $value) {
        // Simulação de PDO::setAttribute
        return true;
    }
}

class PDOStatementWrapper {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function execute($params = []) {
        if (empty($params)) {
            return $this->stmt->execute();
        }
        
        // Bind parameters
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $values[] = $param;
        }
        
        // Bind parameters dynamically
        $bindParams = array_merge([$types], $values);
        $bindMethod = new ReflectionMethod('mysqli_stmt', 'bind_param');
        $bindMethod->invokeArgs($this->stmt, $this->refValues($bindParams));
        
        return $this->stmt->execute();
    }
    
    public function fetch($fetch_style = null) {
        $result = $this->stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function fetchAll($fetch_style = null) {
        $result = $this->stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
}

class PDOResultWrapper {
    private $result;
    
    public function __construct($result) {
        $this->result = $result;
    }
    
    public function fetch($fetch_style = null) {
        return $this->result->fetch_assoc();
    }
    
    public function fetchAll($fetch_style = null) {
        $rows = [];
        while ($row = $this->result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

// Criar wrapper PDO
$conn = new PDOWrapper($mysqli);

// Definir constantes para compatibilidade
if (!defined('PDO::FETCH_ASSOC')) {
    define('PDO::FETCH_ASSOC', 2);
}
?>

<?php
$host = "localhost";
$user = "root";
$password = ""; // Senha vazia no XAMPP
$database = "estoque"; // Banco de dados correto

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Converter mysqli para comportar-se como PDO para compatibilidade
class MysqliDb {
    private $mysqli;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function prepare($query) {
        $stmt = $this->mysqli->prepare($query);
        return new MysqliStatement($stmt);
    }
    
    public function query($query) {
        $result = $this->mysqli->query($query);
        return new MysqliResult($result);
    }
    
    public function exec($query) {
        $this->mysqli->query($query);
        return $this->mysqli->affected_rows;
    }
}

class MysqliStatement {
    private $stmt;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function execute($params = []) {
        if (empty($params)) {
            return $this->stmt->execute();
        }
        
        $types = '';
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
        }
        
        $this->stmt->bind_param($types, ...$params);
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
}

class MysqliResult {
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

// Criar instância compatível com PDO
$conn = new MysqliDb($conn);

// Definir constantes para compatibilidade
if (!defined('PDO::FETCH_ASSOC')) {
    define('PDO::FETCH_ASSOC', 2);
}
?>

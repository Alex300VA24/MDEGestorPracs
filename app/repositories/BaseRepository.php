<?php
namespace App\Repositories;

use App\Config\Database;
use PDO;
use PDOException;

abstract class BaseRepository {
    protected $db;
    protected $table;
    protected $primaryKey = 'ID';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Ejecutar stored procedure con parámetros nombrados
     * @param string $procedureName Nombre del SP
     * @param array $params Parámetros nombrados ['param1' => valor1, ...]
     * @param string $fetchMode 'one' | 'all' | 'none'
     * @return mixed
     */
    protected function executeSP($procedureName, array $params = [], $fetchMode = 'one') {
        try {
            // Construir placeholders nombrados
            $placeholders = [];
            foreach (array_keys($params) as $key) {
                $placeholders[] = "@$key = :$key";
            }
            
            $sql = "EXEC $procedureName";
            if (!empty($placeholders)) {
                $sql .= " " . implode(', ', $placeholders);
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind de parámetros
            foreach ($params as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            
            return $this->handleFetchMode($stmt, $fetchMode);
            
        } catch (PDOException $e) {
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }
    
    /**
     * Ejecutar stored procedure con parámetros posicionales (con ?)
     * @param string $procedureName Nombre del SP
     * @param array $params Array de valores en orden [valor1, valor2, ...]
     * @param string $fetchMode 'one' | 'all' | 'none'
     * @return mixed
     */
    protected function executeSPPositional($procedureName, array $params = [], $fetchMode = 'one') {
        try {
            $placeholders = array_fill(0, count($params), '?');
            $sql = "EXEC $procedureName " . implode(', ', $placeholders);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $this->handleFetchMode($stmt, $fetchMode);
            
        } catch (PDOException $e) {
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Ejecutar stored procedure con parámetro LOB (archivo binario)
     * @param string $procedureName
     * @param array $params Incluye ['archivo' => $binaryData] para LOB
     * @return bool
     */
    protected function executeSPWithLOB($procedureName, array $params) {
        try {
            error_log("Executing SP with LOB: $procedureName");
            error_log("Parameters: " . print_r($params, true));
            $placeholders = array_fill(0, count($params), '?');
            $sql = "EXEC $procedureName " . implode(', ', $placeholders);
            
            $stmt = $this->db->prepare($sql);
            
            $i = 1;
            foreach ($params as $key => $value) {
                if ($key === 'archivo' && $value !== null) {

                    // Convertir string binario a stream
                    $stream = fopen('php://memory', 'r+');
                    fwrite($stream, $value);
                    rewind($stream);

                    $stmt->bindParam(
                        $i,
                        $stream,
                        PDO::PARAM_LOB,
                        0,
                        PDO::SQLSRV_ENCODING_BINARY
                    );

                } else {
                    $type = $this->getParamType($value);
                    $stmt->bindValue($i, $value, $type);
                }
                $i++;
            }

            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }
    
    /**
     * Maneja el modo de obtención de resultados
     */
    private function handleFetchMode($stmt, $fetchMode) {
        switch ($fetchMode) {
            case 'one':
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result ?: null;
                
            case 'all':
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'none':
                return $stmt->rowCount() > 0;
                
            case 'count':
                return $stmt->rowCount();
                
            default:
                return null;
        }
    }
    
    /**
     * Determina el tipo de parámetro PDO
     */
    protected function getParamType($value) {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }
    
    /**
     * Limpiar mensajes de error de ODBC/SQL Server
     */
    protected function cleanErrorMessage($exception) {
        if ($exception instanceof PDOException) {
            $msg = $exception->errorInfo[2] ?? $exception->getMessage();
        } else {
            $msg = $exception->getMessage();
        }
        
        // Limpiar prefijos de ODBC/SQL Server
        if (strpos($msg, ':') !== false) {
            $parts = explode(':', $msg);
            $msg = trim(end($parts));
        }
        
        // Remover prefijos comunes de SQL Server
        $msg = preg_replace('/^\[Microsoft\]\[ODBC Driver.*?\]\[SQL Server\]/', '', $msg);
        $msg = trim($msg);
        
        return $msg;
    }
    
    /**
     * Buscar por ID genérico
     */
    public function findById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findById: " . $e->getMessage());
            throw new \Exception("Error al buscar registro");
        }
    }

    /**
     * Buscar por campo específico
     */
    protected function findBy($field, $value) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE $field = :value";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findBy: " . $e->getMessage());
            throw new \Exception("Error al buscar registro");
        }
    }

    /**
     * Buscar múltiples registros por campo
     */
    protected function findAllBy($field, $value, $orderBy = null) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE $field = :value";
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':value', $value);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findAllBy: " . $e->getMessage());
            throw new \Exception("Error al buscar registros");
        }
    }
    
    /**
     * Listar todos los registros
     */
    public function findAll($orderBy = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findAll: " . $e->getMessage());
            throw new \Exception("Error al listar registros");
        }
    }

    /**
     * Obtener el primer registro que coincida con condiciones
     */
    protected function findFirst(array $conditions, $orderBy = null) {
        try {
            $sql = "SELECT TOP 1 * FROM {$this->table}";
            
            if (!empty($conditions)) {
                $where = [];
                foreach (array_keys($conditions) as $key) {
                    $where[] = "$key = :$key";
                }
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en findFirst: " . $e->getMessage());
            throw new \Exception("Error al buscar primer registro");
        }
    }
    
    /**
     * Insertar registro genérico
     */
    protected function insert(array $data) {
        try {
            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":$field", $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en insert: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Insertar registro y retornar el ID usando OUTPUT
     */
    protected function insertWithOutput(array $data, $outputColumn = null) {
        try {
            $outputColumn = $outputColumn ?? $this->primaryKey;
            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":$field", $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    OUTPUT INSERTED.$outputColumn
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result[$outputColumn] ?? null;
            
        } catch (PDOException $e) {
            error_log("Error en insertWithOutput: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }
    
    /**
     * Actualizar registro genérico
     */
    protected function update($id, array $data) {
        try {
            $fields = array_map(fn($field) => "$field = :$field", array_keys($data));
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
                   " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            foreach ($data as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en update: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Actualizar con condiciones personalizadas
     */
    protected function updateWhere(array $data, array $conditions) {
        try {
            $setFields = array_map(fn($field) => "$field = :set_$field", array_keys($data));
            $whereFields = array_map(fn($field) => "$field = :where_$field", array_keys($conditions));
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setFields) . 
                   " WHERE " . implode(' AND ', $whereFields);
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":set_$key", $value, $type);
            }
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":where_$key", $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error en updateWhere: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }
    
    /**
     * Eliminar registro genérico
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en delete: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Eliminar con condiciones personalizadas
     */
    protected function deleteWhere(array $conditions) {
        try {
            $whereFields = array_map(fn($field) => "$field = :$field", array_keys($conditions));
            
            $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $whereFields);
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error en deleteWhere: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Reiniciar IDENTITY después de eliminar
     */
    protected function reseedIdentity($tableName = null) {
        try {
            $table = $tableName ?? $this->table;
            
            // Obtener el máximo ID actual
            $sqlMax = "SELECT ISNULL(MAX({$this->primaryKey}), 0) AS MaxID FROM $table";
            $stmtMax = $this->db->prepare($sqlMax);
            $stmtMax->execute();
            $row = $stmtMax->fetch(PDO::FETCH_ASSOC);
            $maxID = $row['MaxID'];
            
            // Reiniciar IDENTITY
            $sqlReseed = "DBCC CHECKIDENT ('$table', RESEED, $maxID)";
            $this->db->exec($sqlReseed);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error en reseedIdentity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si existe un registro
     */
    protected function exists($field, $value, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE $field = :value";
            
            if ($excludeId !== null) {
                $sql .= " AND {$this->primaryKey} != :excludeId";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':value', $value);
            
            if ($excludeId !== null) {
                $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] > 0;
        } catch (PDOException $e) {
            error_log("Error en exists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Construir query con filtros dinámicos
     */
    protected function buildFilteredQuery($baseQuery, array $filters, array $allowedFilters) {
        $params = [];
        
        foreach ($filters as $key => $value) {
            if (in_array($key, $allowedFilters) && !empty($value)) {
                if (strpos($key, 'texto') !== false) {
                    $baseQuery .= " AND (";
                    $searchFields = $allowedFilters[$key];
                    $conditions = [];
                    
                    foreach ($searchFields as $field) {
                        $conditions[] = "$field LIKE :$key";
                    }
                    
                    $baseQuery .= implode(' OR ', $conditions) . ")";
                    $params[":$key"] = "%$value%";
                } else {
                    $baseQuery .= " AND $key = :$key";
                    $params[":$key"] = $value;
                }
            }
        }
        
        return ['query' => $baseQuery, 'params' => $params];
    }
    
    /**
     * Ejecutar una transacción de forma segura
     */
    protected function executeTransaction(callable $operations) {
        try {
            $this->db->beginTransaction();
            
            $result = $operations();
            
            $this->db->commit();
            return $result;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Transaction failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Contar registros con filtros opcionales
     */
    protected function count(array $conditions = [], $tableName = null) {
        try {
            $table = $tableName ?? $this->table;
            $sql = "SELECT COUNT(*) as total FROM {$table}";
            
            if (!empty($conditions)) {
                $where = [];
                foreach (array_keys($conditions) as $key) {
                    $where[] = "$key = :$key";
                }
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error en count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar con condiciones personalizadas en tabla específica
     */
    protected function updateWhereTable($tableName, array $data, array $conditions) {
        try {
            $setFields = array_map(fn($field) => "$field = :set_$field", array_keys($data));
            $whereFields = array_map(fn($field) => "$field = :where_$field", array_keys($conditions));
            
            $sql = "UPDATE {$tableName} SET " . implode(', ', $setFields) . 
                   " WHERE " . implode(' AND ', $whereFields);
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":set_$key", $value, $type);
            }
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":where_$key", $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error en updateWhereTable: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Eliminar con condiciones personalizadas en tabla específica
     */
    protected function deleteWhereTable($tableName, array $conditions) {
        try {
            $whereFields = array_map(fn($field) => "$field = :$field", array_keys($conditions));
            
            $sql = "DELETE FROM {$tableName} WHERE " . implode(' AND ', $whereFields);
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($conditions as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue(":$key", $value, $type);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Error en deleteWhereTable: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Ejecutar query personalizada con parámetros
     */
    protected function executeQuery($sql, array $params = [], $fetchMode = 'all') {
        try {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $type = $this->getParamType($value);
                $stmt->bindValue($key, $value, $type);
            }
            
            $stmt->execute();
            
            return $this->handleFetchMode($stmt, $fetchMode);
            
        } catch (PDOException $e) {
            error_log("Error en executeQuery: " . $e->getMessage());
            throw new \Exception($this->cleanErrorMessage($e));
        }
    }

    /**
     * Convertir archivo binario a Base64
     */
    protected function convertBinaryToBase64($binaryData) {
        if (empty($binaryData)) {
            return null;
        }
        return base64_encode($binaryData);
    }

    /**
     * Convertir Base64 a binario
     */
    protected function convertBase64ToBinary($base64Data) {
        if (empty($base64Data)) {
            return null;
        }
        return base64_decode($base64Data);
    }

    /**
     * Limpiar prefijo hexadecimal de SQL Server
     */
    protected function cleanHexPrefix($hexString) {
        if (strpos($hexString, '0x') === 0) {
            return substr($hexString, 2);
        }
        return $hexString;
    }

    /**
     * Formatear array de resultados procesando archivos binarios
     */
    protected function formatBinaryResults(array $results, array $binaryFields = ['Archivo']) {
        return array_map(function($row) use ($binaryFields) {
            foreach ($binaryFields as $field) {
                if (isset($row[$field])) {
                    $row[$field] = $this->convertBinaryToBase64($row[$field]);
                }
            }
            return $row;
        }, $results);
    }
}
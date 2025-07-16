<?php
/**
 * ===== GESTIONNAIRE DE BASE DE DONNÉES - SITE REMMAILLEUSE =====
 * Gestionnaire de base de données avec pool de connexions et cache
 * 
 * @author  Développeur Site Remmailleuse
 * @version 1.0
 * @date    15 juillet 2025
 */

class DatabaseManager {
    private static $instance = null;
    private $connections = [];
    private $config;
    private $maxConnections;
    private $connectionTimeout;
    private $queryCache;
    private $stats;
    
    private function __construct($config = []) {
        $this->config = array_merge([
            'host' => 'localhost',
            'dbname' => 'remmailleuse',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ], $config);
        
        $this->maxConnections = $config['max_connections'] ?? 10;
        $this->connectionTimeout = $config['connection_timeout'] ?? 30;
        $this->queryCache = [];
        $this->stats = [
            'queries_executed' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'connections_created' => 0,
            'connections_reused' => 0,
            'slow_queries' => 0
        ];
    }
    
    /**
     * Obtenir l'instance singleton
     */
    public static function getInstance($config = []) {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Créer une nouvelle connexion PDO
     */
    private function createConnection() {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        
        try {
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
            
            // Configuration additionnelle
            $pdo->exec("SET sql_mode = 'TRADITIONAL'");
            $pdo->exec("SET time_zone = '+00:00'");
            
            $this->stats['connections_created']++;
            
            return $pdo;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir une connexion du pool
     */
    private function getConnection() {
        $connectionId = $this->findAvailableConnection();
        
        if ($connectionId !== null) {
            $this->stats['connections_reused']++;
            return $this->connections[$connectionId]['pdo'];
        }
        
        if (count($this->connections) >= $this->maxConnections) {
            // Attendre qu'une connexion se libère
            $this->waitForAvailableConnection();
            return $this->getConnection();
        }
        
        $pdo = $this->createConnection();
        $connectionId = uniqid();
        
        $this->connections[$connectionId] = [
            'pdo' => $pdo,
            'in_use' => true,
            'created' => time(),
            'last_used' => time()
        ];
        
        return $pdo;
    }
    
    /**
     * Trouver une connexion disponible
     */
    private function findAvailableConnection() {
        foreach ($this->connections as $id => $connection) {
            if (!$connection['in_use']) {
                $this->connections[$id]['in_use'] = true;
                $this->connections[$id]['last_used'] = time();
                return $id;
            }
        }
        return null;
    }
    
    /**
     * Attendre qu'une connexion se libère
     */
    private function waitForAvailableConnection() {
        $timeout = time() + $this->connectionTimeout;
        
        while (time() < $timeout) {
            if ($this->findAvailableConnection() !== null) {
                return;
            }
            usleep(100000); // 100ms
        }
        
        throw new Exception("Timeout: Aucune connexion disponible après {$this->connectionTimeout} secondes");
    }
    
    /**
     * Libérer une connexion
     */
    private function releaseConnection($pdo) {
        foreach ($this->connections as $id => $connection) {
            if ($connection['pdo'] === $pdo) {
                $this->connections[$id]['in_use'] = false;
                $this->connections[$id]['last_used'] = time();
                return;
            }
        }
    }
    
    /**
     * Nettoyer les connexions inactives
     */
    private function cleanupConnections() {
        $now = time();
        $maxIdleTime = 300; // 5 minutes
        
        foreach ($this->connections as $id => $connection) {
            if (!$connection['in_use'] && ($now - $connection['last_used']) > $maxIdleTime) {
                unset($this->connections[$id]);
            }
        }
    }
    
    /**
     * Générer une clé de cache pour une requête
     */
    private function generateCacheKey($sql, $params = []) {
        return md5($sql . serialize($params));
    }
    
    /**
     * Vérifier si une requête est mise en cache
     */
    private function isCacheable($sql) {
        $sql = trim(strtoupper($sql));
        return strpos($sql, 'SELECT') === 0 && 
               strpos($sql, 'NOW()') === false && 
               strpos($sql, 'RAND()') === false &&
               strpos($sql, 'UUID()') === false;
    }
    
    /**
     * Exécuter une requête avec cache
     */
    public function query($sql, $params = [], $cacheTime = 300) {
        $startTime = microtime(true);
        $cacheKey = $this->generateCacheKey($sql, $params);
        
        // Vérifier le cache
        if ($this->isCacheable($sql) && isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            
            if (time() < $cached['expires']) {
                $this->stats['cache_hits']++;
                return $cached['data'];
            } else {
                unset($this->queryCache[$cacheKey]);
            }
        }
        
        $this->stats['cache_misses']++;
        
        // Exécuter la requête
        $pdo = $this->getConnection();
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetchAll();
            
            // Mise en cache si approprié
            if ($this->isCacheable($sql) && $cacheTime > 0) {
                $this->queryCache[$cacheKey] = [
                    'data' => $result,
                    'expires' => time() + $cacheTime,
                    'created' => time()
                ];
            }
            
            $this->stats['queries_executed']++;
            
            // Vérifier les requêtes lentes
            $executionTime = microtime(true) - $startTime;
            if ($executionTime > 1.0) { // Plus d'1 seconde
                $this->stats['slow_queries']++;
                error_log("Requête lente ({$executionTime}s): " . substr($sql, 0, 100));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur SQL: " . $e->getMessage());
        } finally {
            $this->releaseConnection($pdo);
        }
    }
    
    /**
     * Exécuter une requête de modification (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        $startTime = microtime(true);
        $pdo = $this->getConnection();
        
        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            $this->stats['queries_executed']++;
            
            // Invalidation du cache pour les requêtes de modification
            $this->invalidateCache($sql);
            
            // Vérifier les requêtes lentes
            $executionTime = microtime(true) - $startTime;
            if ($executionTime > 1.0) {
                $this->stats['slow_queries']++;
                error_log("Requête lente ({$executionTime}s): " . substr($sql, 0, 100));
            }
            
            return $result;
            
        } catch (PDOException $e) {
            throw new Exception("Erreur SQL: " . $e->getMessage());
        } finally {
            $this->releaseConnection($pdo);
        }
    }
    
    /**
     * Obtenir le dernier ID inséré
     */
    public function getLastInsertId() {
        $pdo = $this->getConnection();
        try {
            return $pdo->lastInsertId();
        } finally {
            $this->releaseConnection($pdo);
        }
    }
    
    /**
     * Commencer une transaction
     */
    public function beginTransaction() {
        $pdo = $this->getConnection();
        return $pdo->beginTransaction();
    }
    
    /**
     * Confirmer une transaction
     */
    public function commit() {
        $pdo = $this->getConnection();
        try {
            return $pdo->commit();
        } finally {
            $this->releaseConnection($pdo);
        }
    }
    
    /**
     * Annuler une transaction
     */
    public function rollback() {
        $pdo = $this->getConnection();
        try {
            return $pdo->rollback();
        } finally {
            $this->releaseConnection($pdo);
        }
    }
    
    /**
     * Invalider le cache pour une table spécifique
     */
    private function invalidateCache($sql) {
        $sql = trim(strtoupper($sql));
        
        // Extraire les noms de tables
        $tables = [];
        if (preg_match_all('/(?:FROM|JOIN|UPDATE|INTO)\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i', $sql, $matches)) {
            $tables = array_unique($matches[1]);
        }
        
        // Invalider le cache pour les requêtes concernant ces tables
        foreach ($this->queryCache as $key => $cached) {
            $cachedSql = $cached['sql'] ?? '';
            
            foreach ($tables as $table) {
                if (stripos($cachedSql, $table) !== false) {
                    unset($this->queryCache[$key]);
                    break;
                }
            }
        }
    }
    
    /**
     * Vider le cache des requêtes
     */
    public function clearCache() {
        $count = count($this->queryCache);
        $this->queryCache = [];
        return $count;
    }
    
    /**
     * Nettoyer le cache expiré
     */
    public function cleanExpiredCache() {
        $now = time();
        $cleaned = 0;
        
        foreach ($this->queryCache as $key => $cached) {
            if ($now >= $cached['expires']) {
                unset($this->queryCache[$key]);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Obtenir les statistiques
     */
    public function getStats() {
        $this->cleanupConnections();
        
        return [
            'connections' => [
                'active' => count($this->connections),
                'max' => $this->maxConnections,
                'created' => $this->stats['connections_created'],
                'reused' => $this->stats['connections_reused']
            ],
            'queries' => [
                'executed' => $this->stats['queries_executed'],
                'slow' => $this->stats['slow_queries']
            ],
            'cache' => [
                'entries' => count($this->queryCache),
                'hits' => $this->stats['cache_hits'],
                'misses' => $this->stats['cache_misses'],
                'hit_rate' => $this->stats['cache_hits'] > 0 ? 
                    round(($this->stats['cache_hits'] / ($this->stats['cache_hits'] + $this->stats['cache_misses'])) * 100, 2) : 0
            ]
        ];
    }
    
    /**
     * Méthodes helper pour les opérations courantes
     */
    
    /**
     * Obtenir un enregistrement par ID
     */
    public function findById($table, $id, $cacheTime = 300) {
        $result = $this->query("SELECT * FROM `$table` WHERE id = ? LIMIT 1", [$id], $cacheTime);
        return $result ? $result[0] : null;
    }
    
    /**
     * Obtenir tous les enregistrements d'une table
     */
    public function findAll($table, $orderBy = 'id', $direction = 'ASC', $cacheTime = 300) {
        $sql = "SELECT * FROM `$table` ORDER BY `$orderBy` $direction";
        return $this->query($sql, [], $cacheTime);
    }
    
    /**
     * Obtenir des enregistrements avec pagination
     */
    public function findPaginated($table, $page = 1, $perPage = 10, $orderBy = 'id', $direction = 'ASC', $cacheTime = 300) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM `$table` ORDER BY `$orderBy` $direction LIMIT ? OFFSET ?";
        return $this->query($sql, [$perPage, $offset], $cacheTime);
    }
    
    /**
     * Compter les enregistrements
     */
    public function count($table, $where = '', $params = [], $cacheTime = 300) {
        $sql = "SELECT COUNT(*) as count FROM `$table`";
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->query($sql, $params, $cacheTime);
        return $result[0]['count'] ?? 0;
    }
    
    /**
     * Insérer un enregistrement
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->execute($sql, array_values($data));
        return $this->getLastInsertId();
    }
    
    /**
     * Mettre à jour un enregistrement
     */
    public function update($table, $data, $where, $params = []) {
        $fields = array_keys($data);
        $setParts = array_map(function($field) {
            return "`$field` = ?";
        }, $fields);
        
        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE $where";
        
        $allParams = array_merge(array_values($data), $params);
        return $this->execute($sql, $allParams);
    }
    
    /**
     * Supprimer un enregistrement
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `$table` WHERE $where";
        return $this->execute($sql, $params);
    }
    
    /**
     * Déstructeur - nettoyer les connexions
     */
    public function __destruct() {
        $this->connections = [];
    }
}

/**
 * Instance globale pour faciliter l'usage
 */
function getDB($config = []) {
    return DatabaseManager::getInstance($config);
}

?>
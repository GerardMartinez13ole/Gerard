<?php
// classes/Sql.php
// Classe simple per encapsular operacions amb la base de dades utilitzant PDO.
// Comentaris en català per facilitar la comprensió a altres desenvolupadors.
// Principals responsabilitats:
// - Obrir la connexió PDO a la base de dades (configurable via $config).
// - Proporcionar mètodes helpers per obtenir/usuaris i executar consultes comunes.

class Sql
{
    private $pdo;

    // Constructor
    // Paràmetres:
    // - array $config: pot contenir 'db_host', 'db_name', 'db_user', 'db_pass', 'db_charset'
    // Comportament:
    // - Valida que hi hagi un nom de base de dades (db_name) i estableix la connexió PDO.
    // - Si hi ha un error de connexió llença RuntimeException amb missatge amigable.
    public function __construct(array $config)
    {
        $host = $config['db_host'] ?? 'mysql-8001.dinaserver.com';
        $db   = $config['db_name'] ?? 'carsharing';
        $user = $config['db_user'] ?? 'gerard';
        $pass = $config['db_pass'] ?? 'aFvwd640/w(3';
        $charset = $config['db_charset'] ?? 'utf8mb4';

        // Validació clara si falta el nom de la base de dades
        if (empty($db)) {
            throw new RuntimeException("Falta la configuració de la base de dades: añade 'db_name' en config.php (ej. 'db_name' => 'mi_base_datos').");
        }

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Mensaje amigable per a la depuració
            throw new RuntimeException("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }

    // getUserByEmail
    // Retorna:
    // - array associatiu amb les columnes (nom, correu, contrasenya) si existeix l'usuari
    // - null si no existeix
    // Ús:
    // - Útil per obtenir les dades d'un usuari abans de verificar la contrasenya.
    public function getUserByEmail(string $email)
    {
        $stmt = $this->pdo->prepare('SELECT  nom, correu, contrasenya FROM usuaris WHERE correu = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // createUser
    // Paràmetres:
    // - string $name, string $email, string $passwordHash (ja hashed)
    // Retorna:
    // - int id del nou usuari si la inserció té èxit
    // - false en cas contrari
    // Nota:
    // - No fa hashing aquí; espera que la contrasenya ja estigui hashejada abans de cridar-ho.
    public function createUser(string $name, string $email, string $passwordHash)
    {
        $stmt = $this->pdo->prepare('INSERT INTO usuaris (nom, correu, contrasenya) VALUES (:name, :email, :password)');
        $ok = $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $passwordHash,
        ]);
        if ($ok) {
            return (int) $this->pdo->lastInsertId();
        }
        return false;
    }

    // verifyUser
    // Paràmetres:
    // - string $email, string $password (plaintext)
    // Comportament:
    // - Busca l'usuari per correu i comprova la contrasenya amb password_verify.
    // Retorna:
    // - array amb les dades de l'usuari (sense la contrasenya) si la verificació és correcta
    // - false si l'usuari no existeix o la contrasenya és incorrecta
    public function verifyUser(string $email, string $password)
    {
        $user = $this->getUserByEmail($email);
        if (!$user)
            return false;
        if (password_verify($password, $user['contrasenya'])) {
            // No devolver la contraseña
            unset($user['contrasenya']);
            return $user;
        }
        return false;
    }

    /**
     * select
     * Executa una SELECT i retorna totes les files.
     * Paràmetres:
     * - string $sql: consulta amb placeholders
     * - array $params: valors per als placeholders
     * Retorna:
     * - array de files (array associatiu)
     */
    public function select(string $sql, array $params = []): array {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * insert
     * Executa una INSERT i retorna el lastInsertId.
     * Ús:
     * - Permet reusar per a insercions personalitzades.
     */
    public function insert(string $sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $this->pdo->lastInsertId();
    }

    /**
     * execute
     * Executa una consulta genèrica (UPDATE/DELETE o altra) i retorna boolean d'èxit.
     */
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * fetch
     * Executa una SELECT i retorna la primera fila (o false si no hi ha res).
     */
    public function fetch(string $sql, array $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

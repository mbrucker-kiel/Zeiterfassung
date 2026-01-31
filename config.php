<?php

$database = __DIR__ . '/assets/db/timetracking.sqlite';
define('ENCRYPTION_KEY', 'your-encryption-key'); 
define('ENCRYPTION_METHOD', 'aes-256-cbc'); 


try {
    $conn = new PDO("sqlite:{$database}", null, null, [
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
        \PDO::ATTR_ERRMODE           => \PDO::ERRMODE_EXCEPTION
    ]);

    // SQL for creating 'zeiterfassung' table
    $createZeiterfassungSql = <<<SQL
CREATE TABLE IF NOT EXISTS zeiterfassung (
    id          INTEGER      PRIMARY KEY AUTOINCREMENT,
    startzeit   TEXT         NOT NULL,
    endzeit     TEXT,
    pause       INTEGER,
    beschreibung TEXT        DEFAULT '' NULL,
    standort    TEXT         DEFAULT '' NULL,
    user_id     INTEGER      NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
SQL;

    // SQL for creating 'Feiertage' table
    $createFeiertageSql = <<<SQL
    CREATE TABLE IF NOT EXISTS Feiertage (
        id  INTEGER  PRIMARY KEY AUTOINCREMENT,
        datum TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL
    );
SQL;

    // SQL for creating 'users' table
    $createUserSql = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        role TEXT NOT NULL DEFAULT 'user',
        token TEXT,
        department_id INTEGER,
        supervisor_id INTEGER,
        regelarbeitszeit REAL DEFAULT 8.0,
        ueberstunden REAL DEFAULT 0.0,
        FOREIGN KEY (department_id) REFERENCES departments(id),
        FOREIGN KEY (supervisor_id) REFERENCES users(id)
    );
SQL;

    // SQL for creating 'departments' table
    $createDepartmentsSql = <<<SQL
    CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );
SQL;

    // SQL for creating 'ldap_settings' table
    $createLdapSettingsSql = <<<SQL
    CREATE TABLE IF NOT EXISTS ldap_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ldap_host TEXT NOT NULL,
        ldap_port INTEGER NOT NULL,
        ldap_user TEXT NOT NULL,
        ldap_pass TEXT NOT NULL,
        ldap_base_dn TEXT NOT NULL
    );
SQL;

    // SQL for creating 'pause_settings' table
    $createPauseSettingsSql = <<<SQL
    CREATE TABLE IF NOT EXISTS pause_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        hours_threshold INTEGER NOT NULL,
        minimum_pause INTEGER NOT NULL
    );
SQL;

    // SQL for creating 'feiertage_settings' table
    $createFeiertageSettingsSql = <<<SQL
    CREATE TABLE IF NOT EXISTS feiertage_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE,
        setting_value TEXT NOT NULL
    );
SQL;

    // Execute the SQL statements
    $conn->exec($createUserSql);
    $conn->exec($createZeiterfassungSql);
    $conn->exec($createFeiertageSql);
    $conn->exec($createDepartmentsSql);
    $conn->exec($createLdapSettingsSql);
    $conn->exec($createPauseSettingsSql);
    $conn->exec($createFeiertageSettingsSql);

    // Check if pause_settings table is empty and insert default values if needed
    $pauseSettingsCount = $conn->query("SELECT COUNT(*) as count FROM pause_settings")->fetch()->count;
    if ($pauseSettingsCount == 0) {
        $conn->exec("INSERT INTO pause_settings (hours_threshold, minimum_pause) VALUES (6, 30), (9, 45)");
    }

    // Check if feiertage_settings table has feiertage_land and insert default value if needed
    $feiertageLandExists = $conn->query("SELECT COUNT(*) as count FROM feiertage_settings WHERE setting_key = 'feiertage_land'")->fetch()->count;
    if ($feiertageLandExists == 0) {
        $conn->exec("INSERT INTO feiertage_settings (setting_key, setting_value) VALUES ('feiertage_land', 'SH')");
    }

    $result = $conn->query("PRAGMA table_info(users)")->fetchAll();
    $columns = array_column($result, 'name');
} catch (\PDOException $e) {
    exit('Could not connect to the SQLite database: ' . $e->getMessage());
}

<?php
/*
Plugin Name: LIMS Plugin
Description: Un plugin LIMS para la gestión de laboratorio en WordPress.
Version: 1.0
Author: Tu Nombre
*/

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) exit;

// Crear tablas al activar el plugin
register_activation_hook(__FILE__, 'lims_create_tables');
function lims_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [
        "CREATE TABLE {$wpdb->prefix}lims_clients (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(255) NOT NULL,
            contact_info TEXT
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}lims_methods (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            method_name VARCHAR(255) NOT NULL,
            variables TEXT
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}lims_samples (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id BIGINT UNSIGNED NOT NULL,
            method_id BIGINT UNSIGNED NOT NULL,
            sample_date DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}lims_quotes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id BIGINT UNSIGNED NOT NULL,
            quote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'abierta'
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}lims_equipment (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            equipment_name VARCHAR(255) NOT NULL,
            verification_date DATE,
            calibration_date DATE,
            maintenance_date DATE,
            status VARCHAR(50) DEFAULT 'operativo'
        ) $charset_collate;"
    ];

    foreach ($tables as $sql) {
        $wpdb->query($sql);
    }
}

// Agregar el menú principal
add_action('admin_menu', 'lims_add_admin_menu');
function lims_add_admin_menu() {
    add_menu_page(
        'LIMS - Gestión de Laboratorio',
        'LIMS',
        'manage_options',
        'lims_dashboard',
        'lims_display_dashboard',
        'dashicons-admin-generic',
        20
    );
    
    add_submenu_page('lims_dashboard', 'Clientes', 'Clientes', 'manage_options', 'lims_clients', 'lims_display_clients');
    add_submenu_page('lims_dashboard', 'Métodos', 'Métodos', 'manage_options', 'lims_methods', 'lims_display_methods');
    add_submenu_page('lims_dashboard', 'Cotizaciones', 'Cotizaciones', 'manage_options', 'lims_quotes', 'lims_display_quotes');
    add_submenu_page('lims_dashboard', 'Equipos', 'Equipos', 'manage_options', 'lims_equipment', 'lims_display_equipment');
    add_submenu_page('lims_dashboard', 'Configuración y Backup', 'Configuración y Backup', 'manage_options', 'lims_settings', 'lims_display_settings');
}

// Funciones para mostrar cada pestaña
function lims_display_dashboard() {
    echo '<h1>LIMS Dashboard</h1><p>Bienvenido al sistema de gestión de laboratorio LIMS.</p>';
}

// Funciones de gestión de Clientes, Métodos y Cotizaciones (se mantienen iguales)
// ...

// Función para mostrar la pestaña de Equipos
function lims_display_equipment() {
    global $wpdb;

    if ($_POST['equipment_name']) {
        $wpdb->insert("{$wpdb->prefix}lims_equipment", [
            'equipment_name' => sanitize_text_field($_POST['equipment_name']),
            'verification_date' => sanitize_text_field($_POST['verification_date']),
            'calibration_date' => sanitize_text_field($_POST['calibration_date']),
            'maintenance_date' => sanitize_text_field($_POST['maintenance_date']),
            'status' => 'operativo'
        ]);
        echo "<p>Equipo agregado.</p>";
    }
    ?>
    <h2>Equipos de Laboratorio</h2>
    <form method="POST">
        <input type="text" name="equipment_name" placeholder="Nombre del equipo" required>
        <label>Fecha de verificación:</label>
        <input type="date" name="verification_date">
        <label>Fecha de calibración:</label>
        <input type="date" name="calibration_date">
        <label>Fecha de mantenimiento:</label>
        <input type="date" name="maintenance_date">
        <input type="submit" value="Agregar Equipo">
    </form>
    <h3>Equipos Existentes</h3>
    <table>
        <tr>
            <th>Nombre del equipo</th>
            <th>Verificación</th>
            <th>Calibración</th>
            <th>Mantenimiento</th>
            <th>Estado</th>
        </tr>
        <?php
        $equipments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lims_equipment");
        foreach ($equipments as $equipment) {
            echo "<tr>
                    <td>{$equipment->equipment_name}</td>
                    <td>{$equipment->verification_date}</td>
                    <td>{$equipment->calibration_date}</td>
                    <td>{$equipment->maintenance_date}</td>
                    <td>{$equipment->status}</td>
                  </tr>";
        }
        ?>
    </table>
    <?php
}

// Configuración y backup (igual que antes)
function lims_display_settings() {
    ?>
    <h2>Configuración y Backup</h2>
    <form method="POST" action="">
        <button type="submit" name="backup_database">Realizar Backup</button>
        <button type="submit" name="optimize_database">Optimizar Base de Datos</button>
    </form>
    <?php
    if (isset($_POST['backup_database'])) {
        lims_backup_database();
        echo "<p>Backup realizado con éxito.</p>";
    }
    if (isset($_POST['optimize_database'])) {
        lims_optimize_database();
        echo "<p>Base de datos optimizada.</p>";
    }
}

// Función para realizar backup (igual que antes)
function lims_backup_database() {
    global $wpdb;
    $tables = $wpdb->tables();
    $backup_content = "";

    foreach ($tables as $table) {
        $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
        foreach ($results as $row) {
            $values = array_map([$wpdb, 'escape'], $row);
            $backup_content .= "INSERT INTO $table VALUES (" . implode(', ', $values) . ");\n";
        }
    }

    $backup_file = WP_CONTENT_DIR . '/lims_backup_' . time() . '.sql';
    file_put_contents($backup_file, $backup_content);
}

// Función para optimizar base de datos (igual que antes)
function lims_optimize_database() {
    global $wpdb;
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}lims_clients");
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}lims_methods");
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}lims_samples");
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}lims_quotes");
    $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}lims_equipment");
}

<?php
/*
Plugin Name: BSV - Anagrafica Admin
Description: Plugin per gestire l'anagrafica utenti (ricerca, modifica) dal backend WordPress.
Version: 1.1b
Author: Mattia Giudici
*/

if (!defined('ABSPATH')) exit;

// === CONNESSIONE DB ESTERNO ===
class BSV_DB_Connector {
    public $conn;

    public function __construct() {
        $this->conn = new mysqli('10.0.7.100', 'remote', 'P@ssw0rd', 'BSV-SEGRETERIA');
        if ($this->conn->connect_error) {
            wp_die('Errore connessione DB esterno: ' . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
    }
}

// === MENU ===
add_action('admin_menu', 'bsv_anagrafica_admin_menu');
function bsv_anagrafica_admin_menu() {
    add_menu_page(
        'Gestione Anagrafica HR',
        'Anagrafica HR',
        'edit_pages',
        'bsv-anagrafica-admin',
        'bsv_anagrafica_admin_page',
        'dashicons-id-alt',
        6
    );
}

// === PAGINA PRINCIPALE ===
function bsv_anagrafica_admin_page() {
    $db = new BSV_DB_Connector();
    $conn = $db->conn;
    $term = isset($_POST['search_term']) ? $conn->real_escape_string($_POST['search_term']) : '';
    echo '<div class="wrap">
        <h1>Gestione Anagrafica HR</h1>
        <form method="POST" action="">
            <input type="text" name="search_term" value="' . esc_attr($term) . '" placeholder="Cerca per nome, cognome o CF" style="width: 300px;">
            <input type="submit" class="button button-primary" value="Cerca">
        </form>';

    if ($term) {
        $sql = "SELECT * FROM anagrafica_hr WHERE `NOME` LIKE '%$term%' OR `COGNOME` LIKE '%$term%' OR `CODICE FISCALE` LIKE '%$term%' LIMIT 100";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            echo '<h2>Risultati</h2><table class="widefat fixed striped">
                <thead><tr><th>Nome</th><th>Cognome</th><th>CF</th><th>Email</th><th>Cellulare</th><th>Azioni</th></tr></thead><tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>
                    <td>' . esc_html($row['NOME']) . '</td>
                    <td>' . esc_html($row['COGNOME']) . '</td>
                    <td>' . esc_html($row['CODICE FISCALE']) . '</td>
                    <td>' . esc_html($row['EMAIL']) . '</td>
                    <td>' . esc_html($row['CELLULARE']) . '</td>
                    <td><a href="' . admin_url('admin.php?page=bsv-anagrafica-admin-edit&id=' . $row['ID']) . '" class="button">Modifica</a></td>
                </tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Nessun risultato trovato.</p>';
        }
    }
    echo '</div>';
}

// === PAGINA MODIFICA ===
add_action('admin_menu', function () {
    add_submenu_page(null, 'Modifica HR', 'Modifica HR', 'edit_pages', 'bsv-anagrafica-admin-edit', 'bsv_anagrafica_admin_edit_page');
});

function bsv_anagrafica_admin_edit_page() {
    $db = new BSV_DB_Connector();
    $conn = $db->conn;
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM anagrafica_hr WHERE ID = $id");
    $row = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fields = ['NOME', 'COGNOME', 'EMAIL', 'CELLULARE', 'CODICE FISCALE'];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = $conn->real_escape_string($_POST[$f]);
        }
        $update = "UPDATE anagrafica_hr SET 
            `NOME`='{$data['NOME']}',
            `COGNOME`='{$data['COGNOME']}',
            `EMAIL`='{$data['EMAIL']}',
            `CELLULARE`='{$data['CELLULARE']}',
            `CODICE FISCALE`='{$data['CODICE FISCALE']}'
            WHERE ID = $id";
        if ($conn->query($update)) {
            echo '<div class="updated"><p>Dati aggiornati con successo.</p></div>';
            $result = $conn->query("SELECT * FROM anagrafica_hr WHERE ID = $id");
            $row = $result->fetch_assoc();
        } else {
            echo '<div class="error"><p>Errore aggiornamento dati.</p></div>';
        }
    }

    echo '<div class="wrap">
        <h1>Modifica Anagrafica HR</h1>
        <form method="POST">';

    foreach ($row as $key => $val) {
        if ($key === 'ID') continue;
        echo '<p><label>' . esc_html($key) . '<br><input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" style="width: 400px;"></label></p>';
    }

    echo '<p><input type="submit" class="button button-primary" value="Salva Modifiche"></p></form></div>';
}

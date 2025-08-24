<?php
/**
 * Script untuk import data bank codes dari JSON ke database
 * Jalankan sekali saja setelah membuat tabel bank_codes
 */

require_once 'admin_functions.php';

function importBankCodes() {
    $db = getDbConnection();
    
    // Baca file JSON
    $jsonFile = __DIR__ . '/kode_bank.json';
    if (!file_exists($jsonFile)) {
        die("File kode_bank.json tidak ditemukan!\n");
    }
    
    $jsonData = file_get_contents($jsonFile);
    $bankCodes = json_decode($jsonData, true);
    
    if (!$bankCodes) {
        die("Gagal membaca data JSON!\n");
    }
    
    // Daftar kode ewallet
    $ewalletCodes = ['shopeepay', 'ovo', 'dana', 'gopay', 'gopay_driver', 'linkaja', 'isaku'];
    
    $insertStmt = $db->prepare("
        INSERT INTO bank_codes (code, name, type, is_active) 
        VALUES (:code, :name, :type, 1)
        ON DUPLICATE KEY UPDATE 
        name = VALUES(name), 
        type = VALUES(type)
    ");
    
    $imported = 0;
    $updated = 0;
    
    foreach ($bankCodes as $bank) {
        $code = $bank['code'];
        $name = $bank['name'];
        $type = in_array(strtolower($code), array_map('strtolower', $ewalletCodes)) ? 'ewallet' : 'bank';
        
        try {
            $insertStmt->execute([
                ':code' => $code,
                ':name' => $name,
                ':type' => $type
            ]);
            
            if ($insertStmt->rowCount() > 0) {
                $imported++;
            } else {
                $updated++;
            }
        } catch (PDOException $e) {
            echo "Error importing {$code}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Import selesai!\n";
    echo "Data baru: {$imported}\n";
    echo "Data diupdate: {$updated}\n";
    echo "Total: " . count($bankCodes) . "\n";
}

// Jalankan import jika script dipanggil langsung
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF']) === 'import_bank_codes.php') {
    importBankCodes();
}
?>
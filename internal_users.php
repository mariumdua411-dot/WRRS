<?php
require_once 'config/db.php';

$official_personnel = [
    [
        'username'  => 'mariam.duwa',
        'password'  => 'collector123',
        'full_name' => 'Mariam Duwa',
        'role'      => 'DATA_COLLECTOR'
    ],
    [
        'username'  => 'ruth.lymo',
        'password'  => 'collector123',
        'full_name' => 'Ruth Lymo',
        'role'      => 'DATA_COLLECTOR'
    ],
    [
        'username'  => 'frank.joseph',
        'password'  => 'manager123',
        'full_name' => 'Frank Joseph',
        'role'      => 'MANAGER'
    ],
    [
        'username'  => 'georgina.matogoro',
        'password'  => 'tech123456',
        'full_name' => 'Georgina Matogoro (Plumber)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'meshuko.lazaro',
        'password'  => 'tech123456',
        'full_name' => 'Meshuko Lazaro (Water Inspector)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'reynalda.musa',
        'password'  => 'tech123456',
        'full_name' => 'Reynalda Musa (Water Distribution Operator)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'suzy.mwaipopo',
        'password'  => 'tech123456',
        'full_name' => 'Suzy Mwaipopo (Hydraulic System Technician)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'victa.mahenge',
        'password'  => 'tech123456',
        'full_name' => 'Victa Mahenge (Water Quality Specialist)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'musuya.sinior',
        'password'  => 'tech123456',
        'full_name' => 'Musuya Sinior (Field Specialist)',
        'role'      => 'TECHNICIAN'
    ],
    [
        'username'  => 'admin',                     
        'password'  => 'password',                     
        'full_name' => 'System Administrator',                   
        'role'      => 'ADMIN'                       
    ],
];

echo "<h2>WRRS Employee Account Initialization</h2>";

foreach ($official_personnel as $person) {
    // Check if account already exists by username
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $person['username']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $hashed_pwd = password_hash($person['password'], PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'ACTIVE')");
        $insert_stmt->bind_param("ssss", $person['username'], $hashed_pwd, $person['full_name'], $person['role']);
        
        if ($insert_stmt->execute()) {
            echo "Successfully created account for: <strong>{$person['full_name']}</strong> [Username: <code>{$person['username']}</code> | Role: {$person['role']}]<br>";
        } else {
            echo "Failed to create account for: {$person['full_name']}<br>";
        }
    } else {
        echo "Account already active: <strong>{$person['full_name']}</strong> [Username: <code>{$person['username']}</code>]<br>";
    }
}

echo "<br><strong>System Database Population Complete!</strong> Direct link to login: <a href='login.php'>Login Page</a>";
?>
<?php
function GetConnection($Connection) {
    if ($Connection == 'ogdbatest01') {
        $server = "OGDBATEST01";
        $user = "dbaccess";
        $password = "mydbaccess";
        $database = "devis"; 
        $conn = odbc_connect("Driver={SQL Server};Server=$server;Database=$database;", $user, $password);
        if (!$conn) {
            die("<div class='alert alert-danger'>Koneksi Gagal: " . odbc_errormsg() . "</div>");
        }
        return $conn;
    }
    return null;
}


$connection = GetConnection('ogdbatest01');


$sql = "SELECT [user], [password] FROM [devis].[dbo].[td_server] WHERE servername = 'otonfsdb00'";
$result = odbc_exec($connection, $sql);

if (!$result) {
    die("<div class='alert alert-danger'>Error in SQL Query: " . odbc_errormsg($connection) . "</div>");
}

$row = odbc_fetch_array($result);


$sqlLogin = $row['user']; 
$password = $row['password']; 


$server = "OTONFSDB00"; 
$connectionOton = odbc_connect("Driver={SQL Server};Server=$server;Database=msdb;", $sqlLogin, $password);

if (!$connectionOton) {
    die("<div class='alert alert-danger'>Koneksi Gagal ke $server: " . odbc_errormsg() . "</div>");
}


$query = "
    SELECT TOP 5
        CONVERT(CHAR(100), SERVERPROPERTY('Servername')) AS Server, 
        msdb.dbo.backupset.database_name, 
        msdb.dbo.backupset.backup_start_date, 
        msdb.dbo.backupset.backup_finish_date, 
        msdb.dbo.backupset.expiration_date, 
        CASE msdb..backupset.type 
            WHEN 'D' THEN 'Database' 
            WHEN 'I' THEN 'Incremental' 
            WHEN 'L' THEN 'Log' 
        END AS backup_type, 
        msdb.dbo.backupset.backup_size, 
        msdb.dbo.backupmediafamily.logical_device_name, 
        msdb.dbo.backupmediafamily.physical_device_name, 
        msdb.dbo.backupset.name AS backupset_name, 
        msdb.dbo.backupset.description 
    FROM msdb.dbo.backupmediafamily 
    INNER JOIN msdb.dbo.backupset ON msdb.dbo.backupmediafamily.media_set_id = msdb.dbo.backupset.media_set_id 
    WHERE (CONVERT(datetime, msdb.dbo.backupset.backup_start_date, 102) >= GETDATE() - 1)  
        AND backupset.type LIKE 'D%' 
    ORDER BY msdb.dbo.backupset.backup_finish_date DESC
";


$resultBackup = odbc_exec($connectionOton, $query);

if (!$resultBackup) {
    die("<div class='alert alert-danger'>Error in SQL Query: " . odbc_errormsg($connectionOton) . "</div>");
}



echo "<table border='1' cellpadding='5' cellspacing='0'>
    <thead>
        <tr>
            <th>Server</th>
            <th>Database Name</th>
            <th>Backup Start Date</th>
            <th>Backup Finish Date</th>
            <th>Expiration Date</th>
            <th>Backup Type</th>
            <th>Backup Size</th>
            <th>Logical Device Name</th>
            <th>Physical Device Name</th>
            <th>Backupset Name</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>";

while ($rowBackup = odbc_fetch_array($resultBackup)) {
    echo "<tr>
        <td>" . htmlspecialchars($rowBackup['Server'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['database_name'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['backup_start_date'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['backup_finish_date'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['expiration_date'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['backup_type'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['backup_size'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['logical_device_name'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['physical_device_name'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['backupset_name'] ?? '') . "</td>
        <td>" . htmlspecialchars($rowBackup['description'] ?? '') . "</td>
    </tr>";
}


odbc_close($connection);
odbc_close($connectionOton);
?>



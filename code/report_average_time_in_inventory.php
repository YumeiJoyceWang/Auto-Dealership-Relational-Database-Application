<!-- get role of user -->
<?php
    $CONST_Anony = "AnonyUser";
    $CONST_OWNER = "Owner";
    $CONST_manager = "manager";
    $CONST_sales_person = "sales_person";
    $CONST_inventory_clerk = "inventory_clerk";
    $CONST_service_writer = "service_writer";

    include('lib/common.php');
    // get role of user
    if (!isset($_SESSION['username'])) {
        echo "<h3><h3>Only accessbile by mananger or owner</h3></h3>";
        echo "<p><a href='login.php'><button>Login</button></a></p>";
        exit;
    }
    else{
        $query = "SELECT role, first_name, last_name FROM loginuser WHERE username = '{$_SESSION['username']}'";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $role = $row['role'];
        if (str_contains($row['role'], 'owner')) {
            $role = $CONST_OWNER;
        }
        else{
            $role = $row['role'];
        }

        //echo $role;
        if ($role != $CONST_manager && $role != $CONST_OWNER){
            echo "<h3>Only accessbile by mananger or owner</h3>";
            echo "<p><a href='login.php'><button>Login</button></a></p>";
            exit;
        }
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];
        echo "<h3>Welcome, {$first_name} {$last_name}, [role: {$role}, username: {$username}]</h3>";   
    }  
?>

<!-- query datebase to get sales information -->
<?php
    $query = <<<EOT
    WITH VinType (VIN, vehicle_type) AS 
    (SELECT VIN, type FROM car 
    UNION SELECT VIN, type FROM truck 
    UNION SELECT VIN, type FROM convertible
    UNION SELECT VIN, type FROM suv
    UNION SELECT VIN, type FROM van)

    , DaysInInventory (VIN, vehicle_type, days_in_inventroy) AS
    (SELECT S.VIN,
        vehicle_type,
        TIMESTAMPDIFF(DAY, add_date, sold_date) + 1 AS days_in_inventroy
    FROM Sale AS S
    INNER JOIN vehicle AS V ON S.VIN = V.VIN 
    INNER JOIN VinType AS VT ON S.VIN = VT.VIN)

    , AvgDaysInventory (vehicle_type, avg_days_inventroy) AS
    (SELECT vehicle_type, avg(days_in_inventroy)
    FROM DaysInInventory
    GROUP BY vehicle_type)

    , AllTypes (vehicle_type) AS
    (SELECT DISTINCT type FROM car 
    UNION SELECT DISTINCT type FROM convertible
    UNION SELECT DISTINCT type FROM Van
    UNION SELECT DISTINCT type FROM truck
    UNION SELECT DISTINCT type FROM suv)

    SELECT AT.vehicle_type AS vehicle_type, IFNULL(avg_days_inventroy, 'NA') as avg_days_inventory
    FROM AllTypes as AT LEFT JOIN AvgDaysInventory AS ADI
    ON AT.vehicle_type = ADI.vehicle_type
    ORDER BY AT.vehicle_type ASC;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: average time in inventory</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Vehicle type</td>
        <td>Average day in inventory</td>
    <tr>
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $avgDays = round($row['avg_days_inventory']);
        $tmp = <<<EOT
        <tr>
            <td class="item_label">{$row['vehicle_type']}</td>
            <td class="item_label">{$avgDays}</td>
        </tr>
        EOT;
        echo $tmp;
    }
    ?>
    
</table>
</body>
</html>
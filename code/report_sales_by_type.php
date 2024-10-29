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
    WITH VehicleType AS
    (SELECT VIN, type FROM car 
        UNION SELECT VIN, type FROM truck 
        UNION SELECT VIN, type FROM convertible
        UNION SELECT VIN, type FROM Van
        UNION SELECT VIN, type FROM suv)

    , SaleTypeTime (VIN, type, days_sold, years_sold) AS
    (SELECT V.VIN, type,
    TIMESTAMPDIFF(DAY, sold_date, MAX(sold_date) OVER()) AS days_sold,
    TIMESTAMPDIFF(YEAR, sold_date, MAX(sold_date) OVER()) AS years_sold
    FROM Sale AS S 
    INNER JOIN Vehicle AS V ON S.VIN = V.VIN
    INNER JOIN VehicleType AS VT ON S.VIN = VT.VIN)

    , SaleByType (type, 30days, oneYear, allTime) AS 
    (SELECT type,
        sum(CASE WHEN days_sold < 30 THEN 1 ELSE 0 END) as 30Days,
        sum(CASE WHEN years_sold = 0 THEN 1 ELSE 0 END) as oneYear,
        count(*) as allTime
    FROM SaleTypeTime
    GROUP BY type)

    , AllTypes (type) AS
    (SELECT DISTINCT type FROM car 
    UNION SELECT DISTINCT type FROM convertible
    UNION SELECT DISTINCT type FROM Van
    UNION SELECT DISTINCT type FROM truck
    UNION SELECT DISTINCT type FROM suv)

    SELECT AT.type as type, 
    IFNULL(30days, 0) AS 30Days, 
    IFNULL(oneYear, 0) AS 1Year, 
    IFNULL(allTime, 0) AS allTime 
    FROM AllTypes AS AT LEFT JOIN SaleByType AS SBT ON AT.type = SBT.type
    ORDER BY AT.type;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: sales by type</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Vehicle type</td>
        <td>Sold in 30 days</td>
        <td>Sold previous year</td>
        <td>Sold all over the time</td>
    <tr>
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $tmp = <<<EOT
        <tr>
            <td class="item_label">{$row['type']}</td>
            <td class="item_label">{$row['30Days']}</td>
            <td class="item_label">{$row['1Year']}</td>
            <td class="item_label">{$row['allTime']}</td>
        </tr>
        EOT;
        echo $tmp;
    }
    ?>
    
</table>
</body>
</html>
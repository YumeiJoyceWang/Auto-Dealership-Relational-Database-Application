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
    WITH VehicleColorCount (VIN, color, color_cnt) AS
    (SELECT VIN, color, count(color) over (partition by VIN) 
    FROM VehicleColor)
    
    , VehicleTrueColor (VIN, true_color) AS
    (SELECT DISTINCT VIN,
            CASE WHEN color_cnt > 1 THEN 'Multiple'
            ELSE color
            END AS true_color
    FROM VehicleColorCount)
    
    , SaleColorDays (VIN, color, days_sold, years_sold) AS
    (SELECT S.VIN,
            true_color,
            TIMESTAMPDIFF(DAY, sold_date, MAX(sold_date) OVER()) AS days_sold,
            TIMESTAMPDIFF(YEAR, sold_date, MAX(sold_date) OVER()) AS years_sold
    FROM Sale AS S INNER JOIN VehicleTrueColor AS VTC ON S.VIN = VTC.VIN)
    
    
    , SaleByColor(color, 30Days, 1Year, allTime) AS
    (SELECT color, 
            sum(CASE WHEN days_sold < 30 THEN 1 ELSE 0 END) AS 30Days,
            sum(CASE WHEN years_sold = 0 THEN 1 ELSE 0 END) as 1Year,
            count(*) as allTime
    FROM SaleColorDays
    GROUP BY color)
    
    
    , AllColors (color) AS
    (SELECT color
    FROM Color
    UNION ALL
    SELECT 'Multiple')
    
    SELECT AC.color as color,
           IFNULL(30days, 0) as 30Days,
           IFNULL(1Year, 0) as 1Year,
           IFNULL(allTime, 0) as allTime
    FROM AllColors AS AC LEFT JOIN SaleByColor AS SBC
    ON AC.color = SBC.color
    ORDER BY AC.color ASC;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: sales by color</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Color</td>
        <td>Sold in 30 days</td>
        <td>Sold previous year</td>
        <td>Sold all over the time</td>
    <tr>
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $tmp = <<<EOT
        <tr>
            <td class="item_label">{$row['color']}</td>
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
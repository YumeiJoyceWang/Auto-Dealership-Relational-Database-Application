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
    WITH SaleManTime (VIN, manufacturer, days_sold, years_sold) AS
    (SELECT V.VIN, M.name,
    TIMESTAMPDIFF(DAY, sold_date, MAX(sold_date) OVER()) AS days_sold,
    TIMESTAMPDIFF(YEAR, sold_date, MAX(sold_date) OVER()) AS years_sold
    FROM Sale AS S 
    INNER JOIN Vehicle AS V ON S.VIN = V.VIN
    INNER JOIN Manufacturer as M ON V.mID = M.mID)

    SELECT manufacturer,
        sum(CASE WHEN days_sold < 30 THEN 1 ELSE 0 END) as 30Days,
        sum(CASE WHEN years_sold = 0 THEN 1 ELSE 0 END) as 1Year,
        count(*) as allTime
    FROM SaleManTime
    GROUP BY manufacturer
    ORDER BY manufacturer;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: sales by manufacturer</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Manufacturer</td>
        <td>Sold in 30 days</td>
        <td>Sold previous year</td>
        <td>Sold all over the time</td>
    <tr>
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $tmp = <<<EOT
        <tr>
            <td class="item_label">{$row['manufacturer']}</td>
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
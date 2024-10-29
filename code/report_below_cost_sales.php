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
    WITH CustomerTypeName AS
    (SELECT customerID, 'Individual' AS customer_type, CONCAT(first_name, ' ', last_name) as customer_name FROM individual
    UNION
    SELECT customerID, 'Business' AS customer_type, business_name as customer_name From business)
    
    SELECT sold_date, invoice_price, sold_price, sold_price / invoice_price * 100.0 AS ratio, customer_name, LU.first_name, LU.last_name 
    FROM Sale AS S INNER JOIN Vehicle AS V ON S.VIN = V.VIN
    INNER JOIN CustomerTypeName as CTN ON S.customerID = CTN.customerID
    INNER JOIN LoginUser as LU ON S.username = LU.username
    WHERE sold_price < invoice_price
    ORDER BY sold_date DESC, sold_price / invoice_price DESC;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: below cost sales</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Sold date</td>
        <td>Invoice price</td>
        <td>Sold price</td>
        <td>Sold/Invoice price ratio(%)</td>
        <td>Customer</td>
        <td>Salesperson</td>
    <tr>
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $color = "background-color:#b300ff";
        if ($row['ratio'] < 95){
            $head = "<tr style = {$color} >";
        }
        else{
            $head = "<tr>";
        }
        $row['ratio'] = number_format($row['ratio'], 2, '.', '');
        $body = <<<EOT

            <td class="item_label">{$row['sold_date']}</td>
            <td class="item_label">{$row['invoice_price']}</td>
            <td class="item_label">{$row['sold_price']}</td>
            <td class="item_label">{$row['ratio']}</td>
            <td class="item_label">{$row['customer_name']}</td>
            <td class="item_label">{$row['first_name']} {$row['last_name']}</td>
        </tr>
        EOT;
        echo $head.$body;
    }
    ?>
    
</table>
</body>
</html>
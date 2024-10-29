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

<!-- query datebase to get monthly sale information -->
<?php
    $query = <<<EOT
    WITH SaleByMonth (VIN, year, month, sold_price, invoice_price) AS
    (SELECT Sale.VIN,
     YEAR(sold_date) AS year,
     Month(sold_date) AS month,
     sold_price,
     invoice_price
    FROM Sale INNER JOIN Vehicle ON Sale.VIN = Vehicle.VIN)

    SELECT year,
     month,
     count(*) as number_sale, 
     sum(sold_price) as total_sale_income,
     sum(sold_price - invoice_price) as total_net_income,
     ((sum(sold_price) / sum(invoice_price)) * 100.0) as ratio
    FROM SaleByMonth 
    GROUP BY year, month
    ORDER BY year DESC, month DESC;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: monthly sales</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Year</td>
        <td>Month</td>
        <td>Number of sold vehicles</td>
        <td>Sales income</td>
        <td>Net income</td>
        <td>Sold/Invoice ratio</td>
        <td>View top salesperson</td>
    <tr>
    
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $color_green = "background-color:#3aeb34";
        $color_yellow = "background-color:#ebd934";
        if ($row['ratio'] >= 125){
            $head = "<tr style = {$color_green} >";
        }
        else if ($row['ratio'] <= 110){
            $head = "<tr style = {$color_yellow} >";
        }
        else{
            $head = "<tr>";
        }
        $row['ratio'] = number_format($row['ratio'], 2, '.', '');
        $tmp = <<<EOT
            <td class="item_label">{$row['year']}</td>
            <td class="item_label">{$row['month']}</td>
            <td class="item_label">{$row['number_sale']}</td>
            <td class="item_label">{$row['total_sale_income']}</td>
            <td class="item_label">{$row['total_net_income']}</td>
            <td class="item_label">{$row['ratio']}</td>
            <td>
                <form action="report_monthly_sales.php" method="post">
                    <button name="values" type="submit" value="year={$row['year']}&month={$row['month']}">Top salesperson</button>
                </form>
            </td>
        </tr>
        EOT;
        echo $head.$tmp;
    }
    ?>
    
</table>

<?php
    if(isset($_POST["values"])){
        $x=$_POST["values"];
        parse_str($x, $output);
        $year = $output['year'];
        $month = $output['month'];
        showTopSalesperson($db, $year, $month);
    }

    function showTopSalesperson($db, $year, $month){
        $query = <<<EOT
        WITH SaleBySomeMonth (saleperson_name, sold_price) AS
         (SELECT CONCAT(LU.first_name, ' ', LU.last_name) as saleperson_name, sold_price 
         FROM Sale INNER JOIN LoginUser AS LU ON Sale.username = LU.username 
         WHERE YEAR(sold_date) = {$year} AND MONTH(sold_date) = {$month})

        SELECT saleperson_name, 
            count(*) as total_vehicles_sold, 
            sum(sold_price) as total_sales
        FROM SaleBySomeMonth
        GROUP BY saleperson_name
        ORDER BY count(*) DESC, sum(sold_price) DESC;
        EOT;
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            echo "<hr>";
            echo "This month/year has no sales";
            return;
        }
        echo "<hr>";
        echo "<table>";
        $title_row = <<<EOT
        <tr>
            <td>Salesperson</td>
            <td>Number of sold vehicles</td>
            <td>Total sales income</td>

        </tr>
        EOT;
        echo $title_row;
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $tmp = <<<EOT
            <tr>
                <td>{$row['saleperson_name']}</td>
                <td>{$row['total_vehicles_sold']}</td>
                <td>{$row['total_sales']}</td>
            </tr>
            EOT;
            echo $tmp;
        }
        echo "</table>";
    }
?>

</body>
</html>

<!-- <form method="POST" action="">
                    <select name="values" onchange="this.form.submit()">
                        <option value="" disabled selected>--select--</option>
                        <option value="year={$row['year']}&month={$row['month']}">Sale details</option>
                    </select>
                </form> -->
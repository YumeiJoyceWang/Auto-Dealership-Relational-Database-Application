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
    WITH PartsCostByID (repairID, parts_cost) AS
    (SELECT repairID, sum(part_quantity * part_price) AS parts_cost
    FROM Part
    GROUP BY repairID)
    
    , CustomerRepair (RepairCustomerID, repair_count, first_repair_date, last_repair_date, repair_charge) AS
    (SELECT R.customerID, count(*),
            min(R.start_date), max(R.start_date), 
            sum(IFNULL(labor_charge, 0) + IFNULL(parts_cost, 0)) 
    FROM Repair AS R LEFT JOIN PartsCostByID AS PCID ON R.repairID = PCID.repairID
    GROUP BY R.customerID)
    
    , CustomerSale (SaleCustomerID, sale_count, first_sale_date, last_sale_date, sale_charge) AS
    (SELECT customerID, count(*), min(sold_date), max(sold_date), sum(sold_price)
    FROM Sale
    GROUP BY customerID)
    
    , CustomerRepairSaleUnion AS
    (SELECT * FROM CustomerRepair as CR
    LEFT JOIN CustomerSale AS CS on CR.RepairCustomerID = CS.SaleCustomerID
    UNION
    SELECT * FROM CustomerRepair AS CR
    RIGHT JOIN CustomerSale AS CS on CR.RepairCustomerID = CS.SaleCustomerID)
    
    
    , CustomerExpense AS
    (SELECT 
           CASE WHEN RepairCustomerID is NOT NULL THEN RepairCustomerID
           ELSE SaleCustomerID
           END AS customerID,
           IFNULL(repair_count, 0) AS repair_count,
           IFNULL(sale_count, 0) as sale_count,
           CASE 
              WHEN first_repair_date IS NULL THEN first_sale_date
              WHEN first_sale_date IS NULL THEN first_repair_date
                ELSE LEAST(first_repair_date, first_sale_date)
           END AS first_date,
           
           CASE 
              WHEN last_repair_date IS NULL THEN last_sale_date
              WHEN last_sale_date IS NULL THEN last_repair_date
                ELSE GREATEST(first_repair_date, first_sale_date)
           END AS last_date,
           
           IFNULL(repair_charge, 0) + IFNULL(sale_charge, 0) as total_charge
    FROM CustomerRepairSaleUnion
    )

    , CustomerExpenseSort AS
    (SELECT * FROM CustomerExpense
    ORDER BY total_charge DESC, last_date DESC
    LIMIT 15
    )
    
    , CustomerTypeName AS
    (SELECT customerID, 'Individual' AS customer_type, CONCAT(first_name, ' ', last_name) as customer_name FROM individual
    UNION
    SELECT customerID, 'Business' AS customer_type, business_name as customer_name From business)
    
    
    SELECT CE.customerID, customer_type, customer_name, first_date, last_date, sale_count, repair_count, total_charge
    FROM CustomerExpenseSort as CE INNER JOIN CustomerTypeName as CTN ON CE.customerID = CTN.customerID
    ORDER BY total_charge DESC, last_date DESC;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: sales by gross customer income</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Customer</td>
        <td>First sale or repair date</td>
        <td>Last sale or reapir date</td>
        <td>Number of sales</td>
        <td>Number of repairs</td>
        <td>Gross income</td>
        <td>Sale and repair details</td>
    <tr>
    
    <?php
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $tmp = <<<EOT
        <tr>
            <td class="item_label">{$row['customer_name']}</td>
            <td class="item_label">{$row['first_date']}</td>
            <td class="item_label">{$row['last_date']}</td>
            <td class="item_label">{$row['sale_count']}</td>
            <td class="item_label">{$row['repair_count']}</td>
            <td class="item_label">{$row['total_charge']}</td>
            <td>
                <form method="POST" action="">
                    <select name="values" onchange="this.form.submit()">
                        <option value="" disabled selected>--select--</option>
                        <option value="customerID={$row['customerID']}&customerType={$row['customer_type']}&detail=sale">Sale details</option>
                        <option value="customerID={$row['customerID']}&customerType={$row['customer_type']}&detail=repair">Repair details</option>
                    </select>
                </form>
            </td>
        </tr>
        EOT;
        echo $tmp;
    }
    ?>
    
</table>

<?php
    if(isset($_POST["values"])){
        $x=$_POST["values"];
        parse_str($x, $output);
        $customerID = $output['customerID'];
        $customerType = $output['customerType'];
        $customerDetail = $output['detail'];
        if ($customerDetail == "sale"){
            showCustomerSale($db, $customerID, $customerType);
        }
        else{
            showCustomerRepair($db, $customerID, $customerType);
        }
    }

    function showCustomerSale($db, $customerID, $customerType){
        $query = <<<EOT
        SELECT sold_date, sold_price, S.VIN, model_year, M.name as manufacturer, model_name, LU.first_name, LU.last_name
        FROM Sale AS S INNER JOIN Vehicle AS V ON S.VIN = V.VIN
        INNER JOIN loginuser AS LU ON S.username = LU.username
        INNER JOIN manufacturer AS M ON V.mID = M.mID
        WHERE S.customerID = {$customerID}
        ORDER BY sold_date DESC, S.VIN ASC;
        EOT;
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            echo "<hr>";
            echo "This customer has no sale";
            return;
        }
        echo "<hr>";
        echo "<table>";
        $title_row = <<<EOT
        <tr>
            <td>Sale date</td>
            <td>Sold price</td>
            <td>VIN</td>
            <td>Model year</td>
            <td>Manufacturer</td>
            <td>Model name</td>
            <td>Salesperson</td>
        </tr>
        EOT;
        echo $title_row;
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $tmp = <<<EOT
            <tr>
                <td>{$row['sold_date']}</td>
                <td>{$row['sold_price']}</td>
                <td>{$row['VIN']}</td>
                <td>{$row['model_year']}</td>
                <td>{$row['manufacturer']}</td>
                <td>{$row['model_name']}</td>
                <td>{$row['first_name']} {$row['last_name']}</td>
            </tr>
            EOT;
            echo $tmp;
        }
        echo "</table>";
    }

    function showCustomerRepair($db, $customerID, $customerType){
        $query = <<<EOT
        WITH PartsCostByID (repairID, parts_cost) AS
        (SELECT repairID, sum(part_quantity * part_price) AS parts_cost
        FROM Part
        GROUP BY repairID)

        SELECT R.customerID AS customerID,
                start_date,
                R.VIN as VIN,
                odometer,
                complete_date,
                IFNULL(parts_cost, 0) as parts_cost,
                labor_charge,
                IFNULL(parts_cost, 0) + IFNULL(labor_charge, 0) AS total_cost,
                first_name,
                last_name
        FROM Repair AS R LEFT JOIN PartsCostByID AS PCID ON R.repairID = PCID.repairID
        INNER JOIN Vehicle AS V ON R.VIN = V.VIN
        INNER JOIN LoginUser as LU ON R.username = LU.username
        WHERE R.customerID = {$customerID}
        ORDER BY start_date DESC, complete_date IS NOT NULL, complete_date DESC, R.VIN ASC;
        EOT;

        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            echo "<hr>";
            echo "This customer has no repair";
            return;
        }
        echo "<hr>";
        echo "<table>";
        $title_row = <<<EOT
        <tr>
            <td>Start date</td>
            <td>End date</td>
            <td>VIN</td>
            <td>Odometer reading</td>
            <td>Parts cost</td>
            <td>Labor cost</td>
            <td>Total cost</td>
            <td>Service writer</td>
        </tr>
        EOT;
        echo $title_row;
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $tmp = <<<EOT
            <tr>
                <td>{$row['start_date']}</td>
                <td>{$row['complete_date']}</td>
                <td>{$row['VIN']}</td>
                <td>{$row['odometer']}</td>
                <td>{$row['parts_cost']}</td>
                <td>{$row['labor_charge']}</td>
                <td>{$row['total_cost']}</td>
                <td>{$row['first_name']} {$row['last_name']}</td>
            </tr>
            EOT;
            echo $tmp;
        }
        echo "</table>";
        
    }
?>


</body>
</html>
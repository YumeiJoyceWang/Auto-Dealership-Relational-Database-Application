<!-- get role of user -->
<?php

include('lib/common.php');
    $CONST_Anony = "AnonyUser";
    $CONST_Owner = "Owner";
    $CONST_manager = "manager";
    $CONST_sales_person = "sales_person";
    $CONST_inventory_clerk = "inventory_clerk";
    $CONST_service_writer = "service_writer";

    // get role of user
    if (!isset($_SESSION['username'])) {
        $role = $CONST_Anony;
        echo "<h3>Welcome customer.</h3>";
    }
    else{
        $query = "SELECT role, first_name, last_name FROM loginuser WHERE username = '{$_SESSION['username']}'";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if (str_contains($row['role'], 'owner')) {
            $role = $CONST_Owner;
        }
        else{
            $role = $row['role'];
        }
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];
        echo "<h3>Welcome, {$first_name} {$last_name}, [role: {$role}, username: {$username}]</h3>";    
    }
?>

<html>
<head>
    <title>Vehicle Details</title>
</head>
<body>

<!-- query datebase to get vehicle detail information -->
<?php

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $VIN = $_GET['VIN'];

        //get vehicle type first
        $query_type = <<<EOT
        WITH VINType (VIN, vehicle_type) AS 
        (SELECT VIN, type FROM car 
        UNION SELECT VIN, type FROM truck 
        UNION SELECT VIN, type FROM convertible
        UNION SELECT VIN, type FROM suv
        UNION SELECT VIN, type FROM Van)

        SELECT vehicle_type FROM VINType AS VT WHERE VT.VIN = '{$VIN}';
        EOT;
        $result = mysqli_query($db, $query_type);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $vehicle_type = $row['vehicle_type'];

        //vehicle color query.
        $query_color = <<<EOT
        WITH VehicleColor (VIN, color) AS
        (SELECT V.VIN, GROUP_CONCAT(color) FROM vehicle AS V INNER JOIN vehiclecolor AS VC on V.VIN = VC.VIN
        GROUP BY V.VIN)

        EOT;


        //$vehicle_type = $_GET['vehicle_type'];
        //$color = $_GET['color'];
        if ($vehicle_type == "Car"){
            $query = <<<EOT
            SELECT is_sold, color, V.VIN, door_num, model_year, model_name, M.name as manufacturer_name, invoice_price, invoice_price * 1.25 as list_price, description
             FROM vehicle AS V INNER JOIN manufacturer AS M on V.mID = M.mID
             INNER JOIN car ON V.VIN = car.VIN
             INNER JOIN VehicleColor as VC ON V.VIN = VC.VIN
             WHERE V.VIN = '{$VIN}';
            EOT;
        }
        elseif($vehicle_type == "Truck"){
            $query = <<<EOT
            SELECT is_sold, color, V.VIN, cargo_capacity, cargocover_type, rear_axle_num, model_year, model_name, M.name as manufacturer_name, invoice_price, invoice_price * 1.25 as list_price, description
             FROM vehicle AS V INNER JOIN manufacturer AS M on V.mID = M.mID
             INNER JOIN Truck ON V.VIN = Truck.VIN
             INNER JOIN VehicleColor as VC ON V.VIN = VC.VIN
             WHERE V.VIN = '{$VIN}';
            EOT;

        }
        elseif($vehicle_type == "Convertible"){
            $query = <<<EOT
            SELECT is_sold, color, V.VIN, roof_type, backseat_num, model_year, model_name, M.name as manufacturer_name, invoice_price, invoice_price * 1.25 as list_price, description
             FROM vehicle AS V INNER JOIN manufacturer AS M on V.mID = M.mID
             INNER JOIN Convertible ON V.VIN = Convertible.VIN
             INNER JOIN VehicleColor as VC ON V.VIN = VC.VIN
             WHERE V.VIN = '{$VIN}';
            EOT;

        }
        elseif($vehicle_type == "Van"){
            $query = <<<EOT
            SELECT is_sold, color, V.VIN, driverside_backdoor, model_year, model_name, M.name as manufacturer_name, invoice_price, invoice_price * 1.25 as list_price, description
             FROM vehicle AS V INNER JOIN manufacturer AS M on V.mID = M.mID
             INNER JOIN Van ON V.VIN = Van.VIN
             INNER JOIN VehicleColor as VC ON V.VIN = VC.VIN
             WHERE V.VIN = '{$VIN}';
            EOT;
        }
        elseif($vehicle_type == "SUV"){
            $query = <<<EOT
            SELECT is_sold, color, V.VIN, drivetrain_type, cupholder_num, model_year, model_name, M.name as manufacturer_name, invoice_price, invoice_price * 1.25 as list_price, description
             FROM vehicle AS V INNER JOIN manufacturer AS M on V.mID = M.mID
             INNER JOIN SUV ON V.VIN = SUV.VIN
             INNER JOIN VehicleColor as VC ON V.VIN = VC.VIN
             WHERE V.VIN = '{$VIN}';
            EOT;
        }
        else{

        }
        $query_final = $query_color.$query;
        $results = mysqli_query($db, $query_final);
        $result = mysqli_fetch_array($results, MYSQLI_ASSOC);
        if ($result["driverside_backdoor"] == '1'){
            $result["driverside_backdoor"] = 'Yes';
        }
        if ($result["driverside_backdoor"] == '0'){
            $result["driverside_backdoor"] = 'No';
        }
    }

?>

<p><a href='main.php'><button>Go to main page</button></a></p>
<h2>Vehicle Detail Information</h2>

<!-- table shows vehicle detail information -->
<table>

    <tr>
        <td class="item_label">VIN</td>
        <td><?php echo $result["VIN"]?></td>
    </tr>

    <tr>
        <td class="item_label">Model Year</td>
        <td><?php echo $result["model_year"]?></td>
    </tr>

    <tr>
        <td class="item_label">Manufacturer</td>
        <td><?php echo $result["manufacturer_name"]?></td>
    </tr>

    <tr>
        <td class="item_label">List Price</td>
        <td><?php echo round($result["list_price"], 2)?></td>
    </tr>

    <tr>
        <td class="item_label">Color</td>
        <td><?php echo $result["color"]?></td>
    </tr>

    <tr>
        <td class="item_label">Description</td>
        <td><?php echo $result['description']?></td>
    </tr>

    <?php
        if ($vehicle_type == "Car"){
            echo "<tr>
                    <td class='item_label'>Number of Doors</td>
                    <td>{$result["door_num"]}</td>
                </tr>";
        }
        else if ($vehicle_type == "Convertible"){
            echo "<tr>
                    <td class='item_label'>Roof Type</td>
                    <td>{$result["roof_type"]}</td>
                </tr>";

            echo "<tr>
                    <td class='item_label'>Number of Backseat</td>
                    <td>{$result["backseat_num"]}</td>
                </tr>";
        }

        else if ($vehicle_type == "Truck"){
            echo "<tr>
                    <td class='item_label'>Cargo Capacity</td>
                    <td>{$result["cargo_capacity"]}</td>
                </tr>";

            echo "<tr>
                    <td class='item_label'>Cargo Cover Type</td>
                    <td>{$result["cargocover_type"]}</td>
                </tr>";
            
            echo "<tr>
                <td class='item_label'>Number of Rear Axle</td>
                <td>{$result["rear_axle_num"]}</td>
            </tr>";
        }

        else if ($vehicle_type == "Van"){
                echo "<tr>
                        <td class='item_label'>Has Driverside Backdoor</td>
                        <td>{$result["driverside_backdoor"]}</td>
                    </tr>";
        }

        else if ($vehicle_type == "SUV"){
            echo "<tr>
                    <td class='item_label'>Drivetrain Type</td>
                    <td>{$result["drivetrain_type"]}</td>
                </tr>";

            echo "<tr>
                    <td class='item_label'>Number of Cup Holder</td>
                    <td>{$result["cupholder_num"]}</td>
                </tr>";
        }

        if ($role == $CONST_Owner || $role == $CONST_inventory_clerk || $role == $CONST_manager){
            $result['invoice_price'] = round( $result['invoice_price'], 2);
            echo "<tr>
                    <td class='item_label'>Invoice price</td>
                    <td>{$result["invoice_price"]}</td>
                </tr>";
        }

    ?>

<table>

<!-- sell the vehicle for owner or salesperson -->
<?php

    if ($role == $CONST_sales_person || $role == $CONST_Owner){
        if ($result["is_sold"] == "0"){
            $link = "sale_form.php?VIN={$result['VIN']}";
            echo "<p><a href='$link'> <button>Sell the vehicle</button></a></p>";
        }
    }

?>

<!-- show inventory information for manager or owner -->
<?php

    if ($role == $CONST_manager || $role == $CONST_Owner){
        echo "<hr>";
        # date and inventory clerk 
        $query = <<<EOT
        SELECT add_date, first_name, last_name FROM vehicle as V INNER JOIN loginuser AS LU ON V.username = LU.username;
        EOT;
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

        $date = $row['add_date'];
        $inventory_clerk = $row['first_name'] . " ". $row['last_name'];
        echo "<p>Date added to inventory: {$date}</p>";

        echo "<p>Name of inventory clerk: {$inventory_clerk}</p>";
    }

?>

<!-- show sale information for manager or owner -->
<?php

    function findCustomer($VIN, $db){
        $query = "SELECT customerID FROM sale WHERE VIN = '{$VIN}';";
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            return "";
        }
        else{
            $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            return $row['customerID'];
        }
    }

    function findCustomerType($customerID, $db){
        $query = "SELECT customerID FROM individual WHERE customerID = '{$customerID}';";
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            return "Business";
        }
        else{
            return "Individual";
        }

    }

    if ($role == $CONST_manager || $role == $CONST_Owner){
        # First, find whether the vehicle has been sold. if yes, find the customer ID
        $customerID = findCustomer($VIN, $db);
        if ($customerID != ""){
            $customerType = findCustomerType($customerID, $db);
            echo "<h2>Vehicle Sale Information</h2>";
            if ($customerType == "Individual"){
                $query = <<<EOT
                SELECT 1.25 * invoice_price as list_price, sold_price, sold_date, CONCAT(LU.first_name, ' ', LU.last_name) as salesperson_name,
                CONCAT(I.first_name, ' ', I.last_name) as customer_name, email, phone, street_address, city, state, postal_code
                FROM sale AS S INNER JOIN loginuser AS LU ON S.username = LU.username
                INNER JOIN vehicle as V ON S.VIN = V.VIN
                INNER JOIN customer AS C ON S.customerID = C.customerID
                INNER JOIN individual as I ON S.customerID = I.customerID
                WHERE S.VIN = '{$VIN}';
                EOT;
                $result = mysqli_query($db, $query);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $row['sold_price'] = round($row['sold_price'], 2);
                $row['list_price'] = round($row['list_price'], 2);
                $table = <<<EOT
                <table>
                    <tr>
                        <td class='item_label'>List Price</td>
                        <td>{$row['list_price']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Sold Price</td>
                        <td>{$row['sold_price']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Sold Date</td>
                        <td>{$row['sold_date']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Salesperson Name</td>
                        <td>{$row['salesperson_name']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Name</td>
                        <td>{$row['customer_name']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Email</td>
                        <td>{$row['email']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Phone</td>
                        <td>{$row['phone']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Street Address</td>
                        <td>{$row['street_address']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer City</td>
                        <td>{$row['city']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer State</td>
                        <td>{$row['state']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Postal Code</td>
                        <td>{$row['postal_code']}</td>
                    </tr>

                </table>
                EOT;
                echo $table;

            }
            else{
                $query = <<<EOT
                SELECT 1.25 * invoice_price as list_price, sold_price, sold_date, CONCAT(LU.first_name, ' ', LU.last_name) as salesperson_name,
                business_name, title, CONCAT(B.contact_first_name, ' ', B.contact_last_name) as contact_name, email, phone, street_address, city, state, postal_code
                FROM sale AS S INNER JOIN loginuser AS LU ON S.username = LU.username
                INNER JOIN vehicle as V ON S.VIN = V.VIN
                INNER JOIN customer AS C ON S.customerID = C.customerID
                INNER JOIN business as B ON S.customerID = B.customerID
                WHERE S.VIN = '{$VIN}';
                EOT;
                $result = mysqli_query($db, $query);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $row['sold_price'] = round($row['sold_price'], 2);
                $row['list_price'] = round($row['list_price'], 2);
                $table = <<<EOT
                <table>
                    <tr>
                        <td class='item_label'>List Price</td>
                        <td>{$row['list_price']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Sold Price</td>
                        <td>{$row['sold_price']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Sold Date</td>
                        <td>{$row['sold_date']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Salesperson Name</td>
                        <td>{$row['salesperson_name']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Business Name</td>
                        <td>{$row['business_name']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Title</td>
                        <td>{$row['title']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Contact Name</td>
                        <td>{$row['contact_name']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Email</td>
                        <td>{$row['email']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Phone</td>
                        <td>{$row['phone']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Street Address</td>
                        <td>{$row['street_address']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer City</td>
                        <td>{$row['city']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer State</td>
                        <td>{$row['state']}</td>
                    </tr>

                    <tr>
                        <td class='item_label'>Customer Postal Code</td>
                        <td>{$row['postal_code']}</td>
                    </tr>

                </table>
                EOT;
                echo $table;
            }
        }
    }
?>

<!-- show repair information for manager or owner -->
<?php
    if ($role == $CONST_manager || $role == $CONST_Owner){
        $query_customerName = <<<EOT
        WITH CustomerName (customerID, customer_name) AS
            (SELECT customerID, CONCAT(first_name, ' ', last_name) as name FROM individual 
            UNION 
            SELECT customerID, business_name as name FROM business)
        EOT;
    
        $query_PartsCost = <<<EOT
    
        , PartsCost (repairID, parts_cost) AS
            (SELECT repairID, sum(part_quantity * part_price) AS parts_cost
            FROM part 
            GROUP BY repairID)
        EOT;
    
        $query_select = <<<EOT
    
        SELECT customer_name, CONCAT(LU.first_name, ' ', LU.last_name) AS servicewriter_name, 
         start_date, complete_date, IFNULL(labor_charge, 0) as labor_charges, IFNULL(parts_cost, 0) as part_charges, 
         (IFNULL(labor_charge, 0) + IFNULL(parts_cost, 0)) AS total_charges
         FROM Repair as R 
         INNER JOIN CustomerName as CN ON R.customerID = CN.customerID
         INNER JOIN loginuser as LU ON R.username = LU.username
         LEFT JOIN PartsCost as PC ON R.repairID = PC.repairID
         WHERE R.VIN = '{$VIN}';
        EOT;
    
        $query = $query_customerName.$query_PartsCost.$query_select;
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) > 0){
            echo "<h2>Vehicle Repair Information</h2>";
            echo "<table>";
            $first_row = <<<EOT
            <tr>
                <td class="item_label">Customer</td>
                <td class="item_label">Service Writer</td>
                <td class="item_label">Start Date</td>
                <td class="item_label">Complete Date</td>
                <td class="item_label">Labor Charge</td>
                <td class="item_label">Part Charge</td>
                <td class="item_label">Total Charge</td>
            </tr>
            EOT;
            echo $first_row;
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $tmp_row = <<<EOT
                <tr>
                    <td>{$row['customer_name']}</td>
                    <td>{$row['servicewriter_name']}</td>
                    <td>{$row['start_date']}</td>
                    <td>{$row['complete_date']}</td>
                    <td>{$row['labor_charges']}</td>
                    <td>{$row['part_charges']}</td>
                    <td>{$row['total_charges']}</td>
                </tr>
                EOT;
                echo $tmp_row;
            }
            echo "</table>";
        }

    }
?>

</body>
</html>




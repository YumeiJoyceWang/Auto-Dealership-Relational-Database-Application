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

<!-- get vehile types -->
<?php
    $query = <<<EOT
    WITH VINType (VIN, type) AS 
    (SELECT VIN, type FROM car 
    UNION SELECT VIN, type FROM truck 
    UNION SELECT VIN, type FROM convertible
    UNION SELECT VIN, type FROM suv
    UNION SELECT VIN, type FROM Van)
    SELECT DISTINCT type
    FROM vehicle AS V INNER JOIN VINType ON V.VIN = VINType.VIN
    WHERE CASE WHEN '{$role}' != '{$CONST_manager}' and '{$role}' != '{$CONST_Owner}' THEN is_sold = 0 ELSE 0 = 0 END;
    EOT;
    
    $all_vehicle_types = mysqli_query($db, $query); 
?>

<!-- get manufacturers -->
<?php 
    $query = <<<EOT
    SELECT DISTINCT M.name as m_name
    FROM vehicle AS V INNER JOIN manufacturer AS M ON V.mID = M.mID
    WHERE CASE WHEN '{$role}' != '{$CONST_manager}' and '{$role}' != '{$CONST_Owner}' THEN is_sold = 0 ELSE 0 = 0 END;
    EOT;

    $all_manufacturer = mysqli_query($db, $query);
?>

<!-- get model years -->
<?php 
    $query = <<<EOT
    SELECT DISTINCT model_year
    FROM vehicle
    WHERE CASE WHEN '{$role}' != '{$CONST_manager}' and '{$role}' != '{$CONST_Owner}' THEN is_sold = 0 ELSE 0 = 0 END
    ORDER BY model_year;
    EOT;
    $all_model_year = mysqli_query($db, $query);
?>

<!-- get all colors -->
<?php 
    $query = <<<EOT
    SELECT DISTINCT color
    FROM vehicle as V INNER JOIN vehiclecolor AS VC ON V.VIN = VC.VIN
    WHERE CASE WHEN '{$role}' != '{$CONST_manager}' or '{$role}' != '{$CONST_Owner}' THEN is_sold = 0 ELSE 0 = 0 END
    ORDER BY color;
    EOT;
    $all_color = mysqli_query($db, $query);
?>

<!doctype html>
<html lang="en">
<head>
    <title>vehicle search page</title>
</head>
<body>


<!-- query datebase to return vehicles -->
<?php
    /*
    $hideSoldFilter = "";
    if ($role != $CONST_manager && $role != $CONST_Owner){
        $hideSoldFilter = "display:none";
    }
    */

    $showSoldFilter = "display:none";
    if ($role == $CONST_Owner || $role == $CONST_manager){
        $showSoldFilter = "";
    }

    $showAddVehicle = "display:none";
    if ($role == $CONST_inventory_clerk || $role == $CONST_Owner){
        $showAddVehicle = "display:block";
    }
    
    $showRepairForm = "display:none";
    if ($role == $CONST_service_writer || $role == $CONST_Owner){
        $showRepairForm = "display:block";
    }

    $showViewReport = "display:none";
    if ($role == $CONST_manager || $role == $CONST_Owner){
        $showViewReport = "display:block";
    }

    $showVIN = "display:none";
    if ($role != $CONST_Anony){
        $showVIN = "";
    }
        
    #get vehicle search info
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $vehicle_type = $_POST['vehicleType'];
        #echo $vehicle_type;
        #echo "<br>";
        $manufacturer = $_POST['manufacturer'];
        #echo $manufacturer;
        #echo "<br>";
        $model_year = $_POST['model_year'];
        #echo $model_year;
        #echo "<br>";
        $color = $_POST['color'];
        #echo $color;
        #echo "<br>";
        $min_list_price = $_POST['minListPrice'];
        #echo $min_list_price;
        #echo "<br>";
        $max_list_price = $_POST['maxListPrice'];
        #echo $max_list_price;
        #echo "<br>";
        $keyword = $_POST['keyword'];
        #echo $keyword;
        #echo "<br>";
        $VIN = $_POST['VIN'];
        #echo $VIN;
        #echo "<br>";
        $is_sold = $_POST['is_sold'];
        if ($role != $CONST_manager && $role != $CONST_Owner){
            $is_sold = '0';
        }
        #echo "<br>";

        // search vehicle
        $query = <<<EOT
        WITH VINType (VIN, vehicle_type) AS 
        (SELECT VIN, type FROM car 
        UNION SELECT VIN, type FROM truck 
        UNION SELECT VIN, type FROM convertible
        UNION SELECT VIN, type FROM suv
        UNION SELECT VIN, type FROM Van),
         vehicleColorList (VIN, colorlist) AS
        (SELECT V.VIN, GROUP_CONCAT(color) FROM vehicle AS V INNER JOIN vehiclecolor AS VC on V.VIN = VC.VIN
        GROUP BY V.VIN)
         SELECT V.VIN, model_name, model_year, invoice_price, invoice_price * 1.25 as list_price, vehicle_type, M.name as manufacturer_name, VCL.colorlist as color, description
          FROM vehicle AS V
          INNER JOIN VINType AS VT ON V.VIN = VT.VIN 
          INNER JOIN Manufacturer AS M on V.mID = M.mID
          INNER JOIN VehicleColorList AS VCL on V.VIN = VCL.VIN
          WHERE
          CASE WHEN '{$VIN}' != '' THEN V.VIN = '{$VIN}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$vehicle_type}' != '' THEN vehicle_type = '{$vehicle_type}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$manufacturer}' != '' THEN M.name = '{$manufacturer}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$model_year}' != '' THEN model_year = '{$model_year}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$color}' != '' THEN colorlist LIKE '%{$color}%' ELSE 1 = 1 END
          AND
          CASE WHEN '{$min_list_price}' != '' THEN invoice_price * 1.25 >= '{$min_list_price}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$max_list_price}' != '' THEN invoice_price * 1.25 <= '{$max_list_price}' ELSE 1 = 1 END
          AND
          CASE WHEN '{$keyword}' != '' THEN (M.name LIKE BINARY '%{$keyword}%' OR model_year LIKE BINARY '%{$keyword}%' OR model_name LIKE BINARY '%{$keyword}%' OR `description` LIKE BINARY '%{$keyword}%') ELSE 1 = 1 END
          AND
          CASE WHEN '{$is_sold}' != '2' THEN is_sold = '{$is_sold}' ELSE 1 = 1 END
          ORDER BY VIN ASC;
        EOT;
        
        $found_vehicles = mysqli_query($db, $query);
        #include('lib/show_queries.php');

    }
?>

<!-- login or logout based on session -->
<?php
    if (isset($_SESSION['username'])){
        echo "<p><a href='logout.php'><button>Logout</button></a></p>";
    }
    else{
        echo "<p><a href='login.php'><button>Login</button></a></p>";

    }
?>

<!-- avaiable features for login user -->
<p><button onclick="window.location.href='add_vehicle.php';" style=<?php echo $showAddVehicle; ?>>Add vehicle</button></p>

<p><button onclick="window.location.href='repair_form.php';" style=<?php echo $showRepairForm; ?>>Repair vehicle</button></p>


<!-- <p><button onclick="window.location.href='view_reports.php';" style=<?php echo $showViewReport; ?>>View report</button></p> -->
<select name="sample" style=<?php echo $showViewReport;?> onchange="location = this.value;">
    <option value = "" disabled selected>--Please select a report--</option>  
    <option value="report_sales_by_color.php">Sales By Color</option> 
    <option value="report_sales_by_type.php">Sales By Type</option>
    <option value="report_sales_by_manufacturer.php">Sales By Manufacturer</option>
    <option value="report_gross_customer_income.php">Gross Customer Income</option>
    <option value="report_repair_manufacturer_type_model.php">Repairs by Manufacturer/Type/Model</option>
    <option value="report_below_cost_sales.php">Below Cost Sales</option>
    <option value="report_average_time_in_inventory.php">Average Time in Inventory</option>
    <option value="report_parts_statistics.php">Parts Statistics</option>
    <option value="report_monthly_sales.php">Monthly Sales</option>
</select> 

<hr>

<!-- Find number of vehicles for sale -->
<?php
    $query = "SELECT count(*) AS CNT FROM vehicle where is_sold = 0";
    $result = mysqli_query($db, $query);
    include('lib/show_queries.php');
    $num = mysqli_fetch_array($result, MYSQLI_ASSOC);
    echo "<h2>Welcome, and there are {$num['CNT']} vehicles for sale.</h2>"; 
?>

<!-- search vehicle form -->
<form name="searchVehicle" action="main.php" method="post">
    <table>
        <!-- vehicle type -->
        <tr>
            <td class="item_label">Vehicle Type</td>
            <td>
                <select name="vehicleType">
                    <?php
                        # get all vehicle types that the dealer has, not all vehicles in appendix
                        echo "<option value=''>All</option>";
                        while ($row = mysqli_fetch_array($all_vehicle_types, MYSQLI_ASSOC)) {
                            echo "<option value='{$row['type']}'>{$row['type']}</option>";
                        }
                    ?>
                </select>
            </td>
        </tr>

        <!-- manufacturer -->
        <tr>
            <td class="item_label">Manufacturer</td>
            <td>
                <select name="manufacturer">
                    <?php
                    # get all manufacturers that the dealer has, not all manufacturers in appendix
                    echo "<option value=''>All</option>";
                    while ($row = mysqli_fetch_array($all_manufacturer, MYSQLI_ASSOC)) {
                        echo "<option value='{$row['m_name']}'>{$row['m_name']}</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>

        <!-- model year -->
        <tr>
            <td class="item_label">Model Year</td>
            <td>
                <select name="model_year">
                   <option value="">All</option>
                    <?php
                    while ($row = mysqli_fetch_array($all_model_year, MYSQLI_ASSOC)) {
                        echo "<option value='{$row['model_year']}'>{$row['model_year']}</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <!-- color -->
        <tr>
            <td class="item_label">Color</td>
            <td>
                <select name="color">
                    <option value=''>All</option>
                    <?php
                    while ($row = mysqli_fetch_array($all_color, MYSQLI_ASSOC)) {
                        echo "<option value='{$row['color']}'>{$row['color']}</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>     

        <!-- list price low -->
        <tr>
            <td class="item_label">Minimum list price</td>
            <td><input type="number" name="minListPrice" value = ""></td>
        </tr> 

        <!-- list price high -->
        <tr>
            <td class="item_label">Maximum list price</td>
            <td><input type="number" name="maxListPrice" value = ""></td>
        </tr>

        <!-- VIN -->
        <tr style=<?php echo $showVIN; ?>>
            <td class='item_label'>VIN</td>
            <td><input type='text' name='VIN' value = ""></td>
        </tr>

        <!-- keyword -->
        <tr>
            <td class="item_label">keyword</td>
            <td><input type="text" name="keyword" value = ""></td>
        </tr>

        <!-- filter by sold or not -->
        <tr style=<?php echo $showSoldFilter; ?>>
            <td class="item_label">Vehicle Sold Status</td>
            <td>
                <select name="is_sold" type = "number">
                    <?php
                    $options = array("ALL" => 2, "Unsold" => 0, "Sold" => 1);
                    foreach($options as $status => $code){
                        echo "<option value=$code>$status</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>
  
    </table>
    
    <!-- search button to submit vehicle search info -->
    <a href='javascript:searchVehicle.submit();'> <button>Search Vehicle</button></a>

</form>

<!-- display vehicle search results -->
<?php

    if ((mysqli_num_rows($found_vehicles) == 0)){
        echo "<h2>Sorry, it looks like we don’t have that in stock!</h2>";
        exit;
    }

    echo '<h2>Found vehicles: </h2>';

    echo "<table>";
    $tile_row = <<<EOT
    <tr> 
        <td>VIN</td> 
        <td>Model Name</td>
        <td>Model Year</td>
        <td>List Price</td>
        <td>Vehicle Type</td>
        <td>Manufacturer</td>
        <td>Color(s)</td>
        <td>Keyword Match Vehicle Description</td>
        <td>View Details<td>
    </tr>
    EOT;
    /*
    if ($role != $CONST_Anony){
        $tile_row .= " <td>Invoice Price</td>";
    }
    */
    //$tile_row .= "<td>Details</td> </tr>";
    echo $tile_row;

    while ($row = mysqli_fetch_array($found_vehicles, MYSQLI_ASSOC)) {
        //$row['invoice_price'] = round($row['invoice_price'], 2);
        $row['list_price'] = round($row['list_price'], 2);
        $view_detail_link= "view_vehicle_detail.php?VIN={$row['VIN']}";
        $keymatch = false;
        if (($keyword != "") && (strpos($row['description'], $keyword) !== false)){
            $keymatch = true;
        }

        $matched = "No";
        if ($keymatch == true){
            $matched = "Yes";
        }

        $temp = <<<EOT
        <tr>
            <td>{$row['VIN']}</td>
            <td>{$row['model_name']}</td>
            <td>{$row['model_year']}</td>
            <td>{$row['list_price']}</td>
            <td>{$row['vehicle_type']}</td>
            <td>{$row['manufacturer_name']}</td>
            <td>{$row['color']}</td>
            <td>{$matched}</td>
            <td><a href='{$view_detail_link}'>view detail</a></td> 
        </tr>
        EOT;
        /*
        if ($role != "AnonyUser"){
            $temp .= " <td>{$row['invoice_price']}</td>";
        }
        */

        //$keymatch = ($keyword != "") && ((strpos($row['description'], $keyword) !== false));
        /*
        if ($keymatch == true){
            $temp .= " <td><a href='{$view_detail_link}'>view detail(keyword matched vehicle description)</a></td></tr>";
        }
        else{
            $temp .= " <td><a href='{$view_detail_link}'>view detail</a></td> </tr>";
        }
        */

        echo $temp;
    }
    echo "</table>";
?>

</body>
</html>
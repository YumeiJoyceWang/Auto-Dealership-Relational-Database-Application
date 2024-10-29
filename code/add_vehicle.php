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
        echo "<h3>Please login to add a vehicle</h3>";
        echo "<p><a href='login.php'><button>Login</button></a></p>";
        exit;
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
        if ($role != $CONST_inventory_clerk && $role != $CONST_Owner){
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

<!-- php script to add vehicle to database when the form is submitted-->
<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST'){
        #echo "<br>";
        $VIN = $_POST['VIN'];

        #check if VIN in database
        $query_vehicle_exist = "SELECT count(*) as cnt FROM vehicle where VIN = '{$VIN}'";
        $result = mysqli_query($db, $query_vehicle_exist);
        $num = mysqli_fetch_array($result, MYSQLI_ASSOC);

        #echo "<br>";
        #echo $VIN;
        $vehicle_type = $_POST['vehicle_type'];
        #echo "<br>";
        #echo $vehicle_type;

        $invoice_price = $_POST['invoice_price'];
        #echo "<br>";
        #echo $invoice_price;

        $model_name = $_POST['model_name'];
        #echo "<br>";
        #echo $model_name;

        $model_year = $_POST['model_year'];
        #echo "<br>";
        #echo $model_year;

        $manufacturer = $_POST['manufacturer'];
        #echo "<br>";
        #echo $manufacturer;

        $colors = $_POST['color'];
        #foreach ($colors as &$color) {
            #echo "<br>";
            #echo $color;
        #}

        $description = $_POST['description'];
        #echo "<br>";
        #echo $description;


        switch ($vehicle_type){
            case "Car":
                $door_num = $_POST['door_num'];
                if (empty($door_num)){
                    $query_add_type = NULL;
                }
                else{
                    $query_add_type = "INSERT INTO car VALUES ('{$VIN}', 'Car', {$door_num})";
                }
                break;

            case "Convertible":
                $roof_type = $_POST['roof_type'];
                $backseat_num = $_POST['backseat_num'];
                if (empty($roof_type) || empty($backseat_num)){
                    $query_add_type = NULL;
                }
                else{
                    $query_add_type = "INSERT INTO convertible VALUES ('{$VIN}', 'Convertible', '{$roof_type}', {$backseat_num})";
                }
                break;

            case "Truck":
                $cargo_capacity = $_POST['cargo_capacity'];
                $cargocover_type = $_POST['cargocover_type'];
                $rear_axle_num = $_POST['rear_axle_num'];
                if(empty($cargo_capacity) || empty($rear_axle_num)){
                    $query_add_type = NULL;
                }
                else if(empty($cargocover_type)){
                    $query_add_type = "INSERT INTO truck VALUES ('{$VIN}', 'Truck', {$cargo_capacity}, NULL, {$rear_axle_num})";
                }
                else{
                    $query_add_type = "INSERT INTO truck VALUES ('{$VIN}', 'Truck', {$cargo_capacity}, '{$cargocover_type}', {$rear_axle_num})";
                }
                break;

            case "Van":
                $driverside_backdoor = $_POST['driverside_backdoor'];
                if (empty($driverside_backdoor)){
                    $query_add_type = NULL;
                }
                else{
                    $query_add_type = "INSERT INTO Van VALUES ('{$VIN}', 'Van', {$driverside_backdoor})";
                }
                break;

            case "SUV":
                $drivetrain_type = $_POST['drivetrain_type'];
                $cupholder_num = $_POST['cupholder_num'];
                if (empty($drivetrain_type) || empty($cupholder_num)){
                    $query_add_type = NULL;
                }
                else{
                    $query_add_type = "INSERT INTO suv VALUES ('{$VIN}', 'SUV', '{$drivetrain_type}', {$cupholder_num})";
                }
                break;
        }

        
        if (is_null($query_add_type) || empty($VIN) || empty($colors) ||empty($vehicle_type) || empty($model_name) || empty($invoice_price) || empty($manufacturer)){
            echo "Please fill the form. Only descirtion can be empty.";
        }
        else if ($num['cnt'] > 0){
            echo "The vehicle VIN is already in database.";
        }
        else{
            #add vechile to vehicle table
            if (empty($description)){
                $query = <<<EOT
                INSERT INTO vehicle VALUES ('{$VIN}', '{$model_name}', {$model_year}, {$invoice_price}, FALSE, NULL, curdate(),
                (SELECT mID FROM manufacturer WHERE name = '{$manufacturer}'),
                '{$username}');
                EOT;
            }
            else{
                $query = <<<EOT
                INSERT INTO vehicle VALUES ('{$VIN}', '{$model_name}', {$model_year}, {$invoice_price}, FALSE, '{$description}', curdate(),
                (SELECT mID FROM manufacturer WHERE name = '{$manufacturer}'),
                '{$username}');
                EOT;
            }
            $result = mysqli_query($db, $query);

            #add color
            foreach ($colors as &$color) {
                $query = "INSERT INTO vehiclecolor VALUES ('{$VIN}', '{$color}')";
                $result = mysqli_query($db, $query);
            }

            #add vehicle attribute
            $result = mysqli_query($db, $query_add_type);
            header("Location: view_vehicle_detail.php?VIN={$VIN}");
        }

    }
?>

<html>
<head>
    <title>add vehicle</title>
    <style>
        select.select_color_dropdown {
            height: 200px;
        }
    </style>
</head>

<body>
    <p><a href='main.php'><button>Go to main page</button></a></p>
    <h2>Please fill the form to add a vehicle(only description or cargocover_type of truck can be empty).</h2>

    <!-- add vehicle form -->
    <form name="addVehicle" action="add_vehicle.php" method="post">
        <table id = "myTable">
            <!-- <tbody> -->
            <!-- 1: VIN -->
            <tr>
                <td class="item_label">VIN*</td>
                <td><input type="text" name="VIN" value = "" required></td>
            </tr>

            <!-- 2: vehicle_type -->
            <tr>
                <td class="item_label">Vehicle Type*</td>
                <td>
                    <select name="vehicle_type" id = "vts" onchange = "VS()">
                        <?php
                        echo "<option value='' disabled selected>--Please select vehicle type--</option>";
                        $allVehicleTypes = array("Car", "Convertible", "Truck", "Van", "SUV");
                        foreach($allVehicleTypes as $item){
                            echo "<option value='{$item}'>$item</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            
            <script>
                function VS() {
                    function deleteRows(){
                        var tableRef = document.getElementById('myTable');
                        var row_len = tableRef.rows.length;
                        for (let i = row_len - 1; i >= 8; i--){
                            tableRef.deleteRow(i);
                        }
                    }

                    function addRow(label_name, data_type, var_name, var_default){
                        let content;
                        if (var_name == "driverside_backdoor"){
                            content = "<td class='item_label'>Has driverside backdoor*</td>\
                            <td> <select name='driverside_backdoor'>\
                            '<option value='TRUE'>Yes</option>'\
                            '<option value='FALSE'>No</option>'\
                            </select> </td>"
                        }
                        else{
                            if (var_name != "cargocover_type"){
                                content = "<td class='item_label'>" + label_name + "</td> <td><input type=" + data_type + " required name=" + var_name + " value=" + var_default + "></td>";
                            }
                            else{
                                content = "<td class='item_label'>" + label_name + "</td> <td><input type=" + data_type + " name=" + var_name + " value=" + var_default + "></td>";
                            }
                        }
                        var tableRef = document.getElementById('myTable');
                        var newRow = tableRef.insertRow(tableRef.rows.length);
                        newRow.innerHTML = content;
                    }
                    
                    var x = document.getElementById("vts").value;
                    if (x == ""){
                        deleteRows();
                    }
                    else if (x == "Van"){
                        deleteRows();
                        addRow("Driverside Backdoor*", "boolean", "driverside_backdoor", "");
                    }
                    else if (x == "Car"){
                        deleteRows();
                        addRow("Number of Doors*", "number", "door_num", "");
                    }
                    else if (x == "Convertible"){
                        deleteRows();
                        addRow("Roof Type*", "text", "roof_type", "");
                        addRow("Number of Backseat*", "number", "backseat_num", "");
                    }
                    else if (x == "Truck"){
                        deleteRows();
                        var content = "<td class='item_label'> Cargo Capacity* </td> <td><input type='number' step = '0.01' name='cargo_capacity' value='' required></td>";
                        var tableRef = document.getElementById('myTable');
                        var newRow = tableRef.insertRow(tableRef.rows.length);
                        newRow.innerHTML = content;
                        addRow("Cargo Cover Type", "text", "cargocover_type", "");
                        addRow("Number of Rear Axles*", "number", "rear_axle_num", "");
                    }
                    else if (x == "SUV"){
                        deleteRows();
                        addRow("Drivetrain Type*", "text", "drivetrain_type", "");
                        addRow("Number of Cupholder*", "number", "cupholder_num", "");
                    }
                }
            </script>
            
            <!-- 3: invoice price -->
            <tr>
                <td class="item_label">Invoice Price*</td>
                <td><input type="number" step = "0.01" name="invoice_price" value = "" required></td>
            </tr> 

            <!-- 4: model name -->
            <tr>
                <td class="item_label">Model Name*</td>
                <td><input type="text" name="model_name" value = "" required></td>
            </tr> 

            <!-- 5: model year -->
            <tr>
                <td class="item_label">Model Year*</td>
                <td><input type="number" name="model_year" value = "" required></td>
            </tr> 


            <!-- 6: manufacturer -->
            <tr>
                <td class="item_label">Manufacturer*</td>
                <td>
                    <select name="manufacturer">
                        <?php
                        $query = "SELECT name FROM manufacturer";
                        $result = mysqli_query($db, $query);
                        include('lib/show_queries.php');
                        echo "<option value='' disabled selected>--Please select manufacturer--</option>";
                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            echo "<option value='{$row['name']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>

            
            <!-- 7: color -->
            <tr>
                <td class="item_label">Color*</td>
                <td>
                    <select name="color[]" multiple = "multiple" class = "select_color_dropdown">
                        <?php
                            $query = "SELECT color FROM Color;";
                            $result = mysqli_query($db, $query);
                            /*
                            $allColors = array("Aluminum", "Beige", "Black", "Blue", "Brown", "Bronze", "Claret", "Copper", "Cream",
                                            "Gold", "Gray", "Green", "Maroon", "Metallic", "Navy", "Orange", "Pink", "Purple", "Red", "Rose", "Rust", "Silver", "Tan",
                                            "Turquoise", "White", "Yellow");
                            
                            foreach($allColors as $item){
                                echo "<option value=$item>$item</option>";
                            }
                            */
                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                echo "<option value={$row['color']}>{$row['color']}</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>     


            <!-- 8: description -->
            <tr>
                <td class="item_label">Description</td>
                <!-- <td><input type="text" name="description" value = ""></td> -->
                <td><textarea name="description"></textarea></td>
            </tr>
            
            <!-- </tbody> -->
    
        </table>
    
    <a href='javascript:addVehicle.submit();'> <button>Add Vehicle</button></a>
    </form>

</body>
</html>
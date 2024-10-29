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
    
    , ManufactuerRepair AS
    (SELECT M.name AS manufacturer,
            count(*) AS count_repairs,
            sum(IFNULL(parts_cost, 0)) as all_parts_cost,
            sum(IFNULL(labor_charge, 0)) as all_labor_cost,
            sum(IFNULL(parts_cost, 0) + IFNULL(labor_charge, 0)) as total_repairs_cost
    FROM Repair AS R LEFT JOIN PartsCostByID AS PCID ON R.repairID = PCID.repairID
    INNER JOIN Vehicle AS V ON R.VIN = V.VIN
    INNER JOIN Manufacturer as M ON V.mID = M.mID
    GROUP BY M.name)
    
    SELECT
        M.name as manufacturer_name,
        M.mID as manufacturer_id,
        IFNULL(count_repairs, 0) AS count_repairs,
        IFNULL(all_parts_cost, 0) AS all_parts_cost,
        IFNULL(all_labor_cost, 0) AS all_labor_cost,
        IFNULL(total_repairs_cost, 0) AS total_repairs_cost
    FROM Manufacturer AS M LEFT JOIN ManufactuerRepair AS MR ON M.name = MR.manufacturer
    ORDER BY M.name;
    EOT;
    $results = mysqli_query($db, $query);
?>

<html>
<head>
    <title>Report: sales by manufacturer, type, and model</title>
</head>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<table>
    <tr>
        <td>Manufacturer</td>
        <td>Count of repairs</td>
        <td>All parts costs</td>
        <td>All labor costs</td>
        <td>All repair costs</td>
        <td>Repair details</td>
    <tr>
    
    <?php

// <form method="POST" action="">
// <select name="values" onchange="this.form.submit()">
//     <option value="" disabled selected>--select--</option>
//     <option value="mID={$row['manufacturer_id']}&detail=type">Details in Type</option>
//     <option value="mID={$row['manufacturer_id']}&detail=model">Details in Model</option>
// </select>
// </form>
    while ($row = mysqli_fetch_array($results, MYSQLI_ASSOC)) {
        $tmp = <<<EOT
        <tr>
            <td>{$row['manufacturer_name']}</td>
            <td>{$row['count_repairs']}</td>
            <td>{$row['all_parts_cost']}</td>
            <td>{$row['all_labor_cost']}</td>
            <td>{$row['total_repairs_cost']}</td>
            <td>
                <form action="report_repair_manufacturer_type_model.php" method="post">
                    <button name="values" type="submit" value="mID={$row['manufacturer_id']}">view detail</button>
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
        $mID = $output['mID'];
        showRepairsbyTypeModel($db, $mID);
    }

    function showRepairsbyTypeModel($db, $mID){
        $query = <<<EOT
        WITH PartsCostByID (repairID, parts_cost) AS
        (SELECT repairID, sum(part_quantity * part_price) AS parts_cost
        FROM Part
        GROUP BY repairID)

        , VinType (VIN, vehicle_type) AS 
        (SELECT VIN, type FROM car 
        UNION SELECT VIN, type FROM truck 
        UNION SELECT VIN, type FROM convertible
        UNION SELECT VIN, type FROM suv
        UNION SELECT VIN, type FROM Van)
        
        , RepairInfo (repairID, vehicle_type, model_name, parts_cost, labor_cost, total_cost) AS
        (SELECT R.repairID, 
                vehicle_type,
                model_name,
                IFNULL(parts_cost, 0) AS parts_cost,
                IFNULL(labor_charge, 0) AS labor_cost,
                IFNULL(parts_cost, 0) + IFNULL(labor_charge, 0) AS total_cost
        FROM Repair AS R 
        LEFT JOIN PartsCostByID AS PCID ON R.repairID = PCID.repairID
        INNER JOIN Vehicle AS V ON R.VIN = V.VIN
        INNER JOIN VinType AS VT ON R.VIN = VT.VIN
        WHERE V.mID = {$mID})
        
        , RepairbyGroup (repairID, vehicle_type, model_name, model_number_repairs, model_parts_cost, model_labor_cost, model_repair_cost) AS
        (SELECT repairID, vehicle_type, model_name,
                count(*) as model_number_repairs,
                sum(parts_cost) as model_parts_cost,
                sum(labor_cost) as model_labor_cost,
                sum(parts_cost + labor_cost) as model_repair_cost
        FROM RepairInfo
        GROUP BY vehicle_type, model_name)
        
        , RepairTypeModel AS
        (SELECT vehicle_type, model_name, 
                model_number_repairs, model_labor_cost, model_parts_cost, model_repair_cost,
                sum(model_number_repairs) OVER(PARTITION BY vehicle_type) as type_number_repairs,
                sum(model_parts_cost) OVER(PARTITION BY vehicle_type) as type_parts_cost,
                sum(model_labor_cost) OVER(PARTITION BY vehicle_type) as type_labor_cost,
                sum(model_repair_cost) OVER (PARTITION by vehicle_type) as type_repair_cost
        FROM RepairbyGroup)
        
        SELECT vehicle_type, model_name, 
               model_number_repairs, model_labor_cost, model_parts_cost, model_repair_cost,
               type_number_repairs, type_labor_cost, type_parts_cost, type_repair_cost
        FROM RepairTypeModel
        ORDER BY type_number_repairs DESC, vehicle_type DESC, model_number_repairs DESC;
        EOT;
        
        $result = mysqli_query($db, $query);
        if (mysqli_num_rows($result) == 0){
            echo "<hr>";
            echo "This manufacturer has no repair";
            return;
        }
        echo "<hr>";
        echo "<table>";
        $title_row = <<<EOT
        <tr>
            <td>Model/Type</td>
            <td>Count of repairs</td>
            <td>All parts cost</td>
            <td>All labors cost</td>
            <td>All repairs cost</td>
        </tr>
        EOT;
        echo $title_row;
        $preVehicleType = NULL;
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
            $curVehicleType = $row['vehicle_type'];
            if ($curVehicleType != $preVehicleType){
                #show vehicle type
                $typeRow = <<<EOT
                <tr>
                    <td>(Type) {$row['vehicle_type']}</td>
                    <td>{$row['type_number_repairs']}</td>
                    <td>{$row['type_parts_cost']}</td>
                    <td>{$row['type_labor_cost']}</td>
                    <td>{$row['type_repair_cost']}</td>
                </tr>
                EOT;
                echo $typeRow;
            }
            #show vehicle model
            $modelRow = <<<EOT
            <tr>
                <td>(Model) {$row['model_name']}</td>
                <td>{$row['model_number_repairs']}</td>
                <td>{$row['model_parts_cost']}</td>
                <td>{$row['model_labor_cost']}</td>
                <td>{$row['model_repair_cost']}</td>
            </tr>
            EOT;
            echo $modelRow;
            $preVehicleType = $curVehicleType;
        }
        echo "</table>";

    }
?>


</body>
</html>
<!-- get role of user copied from Eric--> 
<!-- only accessible for service writer and owner !-->

<?php

    include('lib/common.php');
    // get role of user
    if (!isset($_SESSION['username'])) {
        header('Location: login.php');
        exit();
    }
    else{
        $query = "SELECT role, first_name, last_name FROM loginuser WHERE username = '{$_SESSION['username']}'";
        $result = mysqli_query($db, $query);
        include('lib/show_queries.php');
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $role = $row['role'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $username = $_SESSION['username'];
        if (!str_contains($role, 'service_writer')) {
            echo "Sorry, you don't have access to repair form!";
            exit;
        }  
    }
    
?>

<?php

	function check_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data); 
		return $data;
	}
?>


<!DOCTYPE html>
<html>
<body>
<p><a href='main.php'><button>Go to main page</button></a></p>
<h1>Repair form</h1>
<hr>

<!-- step 1: search vehicle form !-->
<div name="search_vehicle">Search a vehicle by VIN :</div><br>
<form action="" method="post">
  <input type="text" id="VIN" name="VIN" placeholder="VIN" value="">
  <input type="submit" name="search" value="Search">
</form>

<!-- step 2: search vehicle using vehicle form-->
<?php
	$VIN = "";
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (empty($_POST["VIN"])) {
		$VIN = "Please enter a VIN to search";
		}
		else {
		$VIN = check_input($_POST["VIN"]);
		}


	// step 3: show vehicle detail information use php in step 2 to echo the html code is easier 

        $query = <<<EOT
        WITH VINType (VIN, vehicle_type) AS 
        (SELECT VIN, type FROM car 
        UNION SELECT VIN, type FROM truck 
        UNION SELECT VIN, type FROM convertible
        UNION SELECT VIN, type FROM suv
        UNION SELECT VIN, type FROM van),
        VehicleColorList (VIN, colorlist) AS
        (SELECT V.VIN, GROUP_CONCAT(color) FROM vehicle AS V INNER JOIN vehiclecolor AS VC on V.VIN = VC.VIN
        GROUP BY V.VIN)
        SELECT V.VIN, model_name, model_year, vehicle_type, M.name as manufacturer_name, VCL.colorlist as color
        FROM vehicle AS V
        INNER JOIN VINType AS VT ON V.VIN = VT.VIN 
        INNER JOIN Manufacturer AS M on V.mID = M.mID
        INNER JOIN VehicleColorList AS VCL on V.VIN = VCL.VIN
        WHERE V.VIN = '{$VIN}' and is_sold = 1
        EOT;


		$result = mysqli_query($db, $query);
		include('lib/show_queries.php');

		if (mysqli_num_rows($result) == 0) {
			array_push($error_msg,  "No sold vehicle found!");
			echo "<p>No sold vehicle found!</p>";
            exit;
		} else {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
            echo "<br>";
            echo "<h2>Vechicle detail</h2>";
			echo "<table>";
			$title_row = <<<EOT
			<tr> 
				<td>VIN</td>
				<td>Model Name</td>
				<td>Model Year</td>
				<td>Vehicle Type</td>
				<td>Manufacturer</td>
				<td>Color</td>
			</tr>
			EOT;
			echo $title_row;

			$temp = <<<EOT
			<tr>
				<td>{$row['VIN']}</td>
				<td>{$row['model_name']}</td>
				<td>{$row['model_year']}</td>
				<td>{$row['vehicle_type']}</td>
				<td>{$row['manufacturer_name']}</td>
				<td>{$row['color']}</td>
			</tr>
			EOT;
			echo $temp;

			echo "</table>";
            echo "<hr>";
        
        // step 4: if there is already a repair related to the vehicle, show the repair information 

            $query_repair = <<<EOT
            WITH CustomerName (customerID, customer_name) AS
                (SELECT customerID, CONCAT(first_name, ' ', last_name) as name FROM individual 
                UNION 
                SELECT customerID, business_name as name FROM Business)
        
            SELECT R.VIN, customer_name, CONCAT(LU.first_name, ' ', LU.last_name) AS servicewriter_name, start_date, odometer, complete_date, labor_charge, description, partID, vendor, part_price, part_quantity
            FROM Repair as R 
            INNER JOIN CustomerName as CN ON R.customerID = CN.customerID
            INNER JOIN Loginuser as LU ON R.username = LU.username
            LEFT JOIN Part as P ON R.repairID = P.repairID
            WHERE R.VIN = '{$VIN}'
            ORDER BY complete_date ASC;
            EOT;
        

            $result_repair = mysqli_query($db, $query_repair);

            if (mysqli_num_rows($result_repair) > 0) {
                echo "<h2>Vehicle Repair Information</h2>";
                echo "<table>";
                $first_row = <<<EOT
                <tr>
                    <td class="item_label">VIN</td>
                    <td class="item_label">Customer</td>
                    <td class="item_label">Service Writer</td>
                    <td class="item_label">Start Date</td>
                    <td class="item_label">Odometer</td>
                    <td class="item_label">Complete Date</td>
                    <td class="item_label">Labor Charge</td>
                    <td class="item_label">Description</td>
                    <td class="item_label">PartID</td>
                    <td class="item_label">Vendor</td>
                    <td class="item_label">Part price</td>
                    <td class="item_label">Part quantity</td>
                    <td class="item_label">Edit repair</td>
                    <td class="item_label">Add part</td>
                    <td class="item_label">Complete repair</td>
                </tr>
                EOT;

                echo $first_row;
                while ($row = mysqli_fetch_array($result_repair, MYSQLI_ASSOC)) {
                    $edit_repair_link = "edit_repair.php?VIN={$row['VIN']}&start_date={$row['start_date']}";
                    $add_repair_link = "add_repair.php?VIN={$row['VIN']}";
                    $add_part_link = "add_part.php?VIN={$row['VIN']}&start_date={$row['start_date']}";
                    $complete_repair_link = "complete_repair.php?VIN={$row['VIN']}&start_date={$row['start_date']}";
                    $repair[] = $row;
                    $tmp_row = <<<EOT
                    <tr>
                        <td>{$row['VIN']}</td>
                        <td>{$row['customer_name']}</td>
                        <td>{$row['servicewriter_name']}</td>
                        <td>{$row['start_date']}</td>
                        <td>{$row['odometer']}</td>
                        <td>{$row['complete_date']}</td>
                        <td>{$row['labor_charge']}</td>
                        <td>{$row['description']}</td>
                        <td>{$row['partID']}</td>
                        <td>{$row['vendor']}</td>
                        <td>{$row['part_price']}</td>
                        <td>{$row['part_quantity']}</td>                       
                    
                    EOT;

                    if (is_null($row['complete_date'])){
                        $tmp_row .= "<td><a href='{$edit_repair_link}'>Edit repair</a></td>";
                        $tmp_row .= "<td><a href='{$add_part_link}'>Add part</a></td>";
                        $tmp_row .= "<td><a href='{$complete_repair_link}'>Complete repair</a></td>";
                    }
                    $tmp_row .= "</tr>";
                    echo $tmp_row;
                    

                }             
                foreach ($repair as $row) {
                    if (!is_null($row['complete_date'])){
						echo "<a href='{$add_repair_link}'>Add another repair</a>"; 
                        echo "<br><br>";
                    }  
                break;  
                }
                echo "</table>";

            } else {
                echo "<p>No repair found!</P>";
                $add_repair_link = "add_repair.php?VIN={$row['VIN']}";
                echo "<a href='{$add_repair_link}'</a>Add repair</a>";
                exit;
            }
        }
    }
    


?>
</body>
</html> 
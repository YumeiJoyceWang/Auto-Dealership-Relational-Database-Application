
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
		echo "Sorry, you don't have access to add repair!";
		exit;
	}  
}

?>


<?php
//POST$_POST the VIN to which the repair will be added
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$VIN = $_GET['VIN'];
	$_SESSION['AddRepair_VIN'] = $VIN;
}

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

function is_date( $str ) { 
	$stamp = strtotime( $str ); 
	if (!is_numeric($stamp)) { 
		return false; 
	} 
	$month = date( 'm', $stamp ); 
	$day   = date( 'd', $stamp ); 
	$year  = date( 'Y', $stamp ); 
  
	if (checkdate($month, $day, $year)) { 
		return true; 
	} 
	return false; 
} 

?>

<!DOCTYPE html>
<html>
<body>
<p><a href='repair_form.php'><button>Go to repair form page</button></a></p>
<h1>Add repair</h1>
<hr>

<?php include('lookup_customer.php');?>

<br>
<a href='add_customer.php'> <button>Add Customer</button></a>
<hr>
<h2>Add repair detail</h2>
<div class="add_repair"> 

	<form id="addRepair" name="addRepair" action="" method="post">
	
		<div class="repair_details">
			<label for="VIN">VIN*</label>
			<input type="text" name="VIN" value="<?php echo $_SESSION['AddRepair_VIN']; ?>"/><br><br>
			<label for="odometer">Odometer*</label>
			<input type="number" name="odometer" required/><br><br>
			<label for="startDate">Start date*</label>
			<input type="text" name="start_date" value="<?php echo date("Y-m-d")?>" required/><br><br>
			<label for="customer">CustomerID*</label>
			<input type="text" name="customerID" value=""/><br><br>
			<label for="laborCharge">Labor charge*</label>
			<input type="number" step=0.01 name="labor_charge" required/><br><br>
			<label for="completeDate">Complete date</label>
			<input type="date" name="complete_date" max="<?= date('Y-m-d'); ?>"/><br><br>
			<label for="completeDate">Description*</label>
			<textarea name="description" rows="4" cols="50" required> </textarea><br><br>
            <input type="submit" name="addRepair" value="Add repair"/>
		</div>
    </form>
</div>
<hr>
</body>
</html> 

<?php

	//check input validation and save the input to database

	if (isset($_POST["addRepair"])) {
		$VIN = $_SESSION['AddRepair_VIN'];
		$customerID = $_POST["customerID"];
		$odometer = $_POST["odometer"];
		$laborCharge = $_POST["labor_charge"];
		$startDate = $_POST["start_date"];
		$description = test_input($_POST["description"]);
		$completeDate = $_POST["complete_date"];

		$query_startDate = "SELECT count(*) as cnt FROM Repair as R where VIN = '{$VIN}' and R.start_date = '{$startDate}' ";
        $result = mysqli_query($db, $query_startDate);
        $num = mysqli_fetch_array($result, MYSQLI_ASSOC);

		// check if customer is in db.
        $query1 = <<<EOT
        SELECT customerID        
        FROM customer
        WHERE customerID = '{$customerID}';
        EOT;
        $result1 = mysqli_query($db, $query1);

		if (empty($laborCharge) || empty($VIN) || empty($customerID) || empty($odometer) || empty($startDate) || empty($description)) {
			echo "Please fill in the field, only complete date can be empty!";
			exit;
		} 
		if ($num['cnt'] > 0){
			echo "Erro: A vehicle will never have more than one repair starting on the same date!";
			exit;
		}
		if (mysqli_num_rows($result1) == 0){
			echo 'Cannot find this customer, please make sure the info is correct!';
			exit;
		}

		if (empty($completeDate)) {
			$query_insert = <<<EOT
			INSERT INTO Repair (VIN, username, customerID, odometer, labor_charge, start_date, description, complete_date)
			VALUES ('{$VIN}', '{$username}', {$customerID}, {$odometer}, {$laborCharge}, '{$startDate}', '{$description}', NULL)
			EOT;

					
		} else {
			if ($completeDate < $startDate) {
				array_push($error_msg, "Error: Complete date can't be before start date!");
				echo "Complete date can't be before start date!";
				exit;
			} else {
				$query_insert = <<<EOT
				INSERT INTO Repair (VIN, username, customerID, odometer, labor_charge, start_date, description, complete_date)
				VALUES ('{$VIN}', '{$username}', {$customerID}, {$odometer}, {$laborCharge}, '{$startDate}', '{$description}', '{$completeDate}')
				EOT;
				
			}
		}

		$result_insert = mysqli_query($db, $query_insert);

		if (!empty($laborCharge) and !empty($VIN) and !empty($customerID) and !empty($odometer) and !empty($startDate) and !empty($description)) {		
			
			echo "<h3>Added repair information</h3>";
			echo "<table>";

			$first_row = <<<EOT
			<tr>
				<td class="item_label">VIN</td>
				<td class="item_label">Service writer</td>
				<td class="item_label">CustomerID</td>
				<td class="item_label">Odometer</td>
				<td class="item_label">Start Date</td>
				<td class="item_label">Labor Charge</td>
				<td class="item_label">Description</td>
				<td class="item_label">Complete Date</td>	
			</tr>
			EOT;

			echo $first_row;

			$tmp_row = <<<EOT
			<tr>
				<td>{$VIN}</td>
				<td>{$username}</td>
				<td>{$customerID}</td>
				<td>{$odometer}</td>
				<td>{$startDate}</td>
				<td>{$laborCharge}</td>
				<td>{$description}</td>
				<td>{$completeDate}</td>			
			</tr>
			EOT;

			echo $tmp_row;
			echo "</table>";  
			echo "<br>";
			echo "<hr>";
			
		}		
	}
?>


<?php 
$add_part_link = "add_part.php?VIN={$VIN}&start_date={$_POST['start_date']}";
echo "If parts are need, please ";
echo "<a href='{$add_part_link}'><button>Add part</button></a>";
echo "<br>";
?>




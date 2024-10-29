<!-- Edit repair can only edit labor charge!-->

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
        $row1 = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $role = $row1['role'];
        $first_name = $row1['first_name'];
        $last_name = $row1['last_name'];
        $username = $_SESSION['username'];
        if (!str_contains($role, 'service_writer')) {
          echo "Sorry, you don't have access to edit repair!";
          exit;
        }    
    }

// get the VIN and start date to update the repair 
 
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $VIN = $_GET['VIN'];
  $_SESSION['editRepair_VIN'] = $VIN;
  $startDate = $_GET["start_date"];
  $_SESSION['editRepair_startDate'] = $startDate;
}
 

// get/view repair information

$query_repair = <<<EOT
WITH CustomerName (customerID, customer) AS
    (SELECT customerID, driver_license as identity FROM individual 
    UNION 
    SELECT customerID, taxID as identity FROM Business)

SELECT R.VIN, CN.customer, CONCAT(LU.first_name, ' ', LU.last_name) AS servicewriter_name, odometer, R.start_date, complete_date, labor_charge, description, partID, vendor, part_price, part_quantity
FROM Repair as R 
INNER JOIN CustomerName as CN ON R.customerID = CN.customerID
INNER JOIN Loginuser as LU ON R.username = LU.username
LEFT JOIN Part as P ON R.repairID = P.repairID
WHERE R.VIN = '{$VIN}' and start_date = '{$startDate}';

EOT;


$result_repair = mysqli_query($db, $query_repair);
include('lib/show_queries.php');


if (mysqli_num_rows($result_repair) > 0) {
  $row = mysqli_fetch_array($result_repair, MYSQLI_ASSOC);
} else {
  array_push($error_msg,  "Query ERROR: Failed to get repair information");
}

?>


<!DOCTYPE html>
<html>
<head>
  <title>Edit repair page</title>
</head>
<p><a href='repair_form.php'><button>Go to repair form page</button></a></p>
<body>
  <h1>Edit repair</h1>
  <div class="edit_repair">
	  <form id="input" name="editRepair" action="" method="post">
      <p> Hello, you can only update labor charge.</p>
      <label for="VIN">VIN</label>
			<input type="text" name="VIN" value="<?php echo $VIN?>" readonly><br><br>
      <label for="customer">Customer</label>
      <input type="text" name="customer" value="<?php echo $row['customer'];?>" readonly><br><br>
      <label for="odometer">Odometer</label>
      <input type="number" name="odometer" value="<?php echo $row['odometer'];?>" readonly><br><br>
      <label for="startDate">Start date</label>
			<input type="text" name="startDate" value="<?php echo $startDate;?>" readonly><br><br>
      <label for="description">Description </label>
      <input type="text" name="description" value="<?php echo $row['description'];?>" readonly><br><br>
      <label for="laborCharge">Labor charge</label>
      <input type="number" step=0.01 name="laborCharge" value="<?php echo $row['labor_charge'];?>"><br><br>
      <!--<label for="completeDate">Complete date</label>
      <input type="date" name="completeDate" value=""><br><br>!-->
      <input type="hidden" name="loginuserRole" value="<?php echo $role;?>">
      <input type="hidden" name="preLaborCharge" value="<?php echo $row['labor_charge'];?>">
      <input type="submit" name="editRepair" value="Update labor charge">
		</form>

  </div> 
  
  <hr>   
</body>
</html>
    
<?php


//echo $_SESSION['role'];

if (isset($_POST["editRepair"])) {
    $VIN = $_SESSION['editRepair_VIN'];
    $customer = $_POST["customer"];
    $odometer = $_POST["odometer"];
    $laborCharge = $_POST["laborCharge"];
    $startDate = $_SESSION["editRepair_startDate"];
    $description = $_POST['description'];
    //$completeDate = $_POST['completeDate'];
    $userRole = $_POST['loginuserRole'];
    $preLaborCharge = $_POST['preLaborCharge'];

    //service writer can't update the labor charge if less than the previous value
    if ($laborCharge < $preLaborCharge) {
      if ($userRole == "service_writer") {
        array_push($error_msg,  "Error: Can't update labor charge. It must be higher than current value!");
        echo "Can't update labor charge. It must be higher than current value!";
        exit;
      }
    }

    if ( !empty($laborCharge)) {

      $query_new = "UPDATE Repair as R
      SET labor_charge = {$laborCharge}
      WHERE VIN = '{$VIN}' and R.start_date = '{$startDate}' ";

      $result_new = mysqli_query($db, $query_new);
      include('lib/show_queries.php');

      if (mysqli_affected_rows($db) == -1) {
        array_push($error_msg,  "UPDATE ERROR: Repair information.");
      }

    } 
    echo "<h3>Vehicle Repair updated:</h3>";
    echo "<table>";

			$first_row = <<<EOT
			<tr>
				<td class="item_label">VIN</td>
				<td class="item_label">Customer</td>
				<td class="item_label">Odometer</td>
				<td class="item_label">Start Date</td>
				<td class="item_label">Labor Charge</td>
				<td class="item_label">Description</td>
					
			</tr>
			EOT;

			echo $first_row;

			$tmp_row = <<<EOT
			<tr>
				<td>{$VIN}</td>
				<td>{$customer}</td>
				<td>{$odometer}</td>
				<td>{$startDate}</td>
				<td>{$laborCharge}</td>
				<td>{$description}</td>
							
			</tr>
			EOT;

			echo $tmp_row;
			echo "</table>";  
			echo "<br>";
      echo "<hr>";


}  //end of if(isset)
?>


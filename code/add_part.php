

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
		echo "Sorry, you don't have access to add parts!";
		exit;
	}  
}

 
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $VIN = $_GET['VIN'];
  //$_SESSION['editRepair_VIN'] = $VIN;
  $startDate = $_GET["start_date"];
  //$_SESSION['editRepair_startDate'] = $startDate;
}
 
?>

<!DOCTYPE html>
<html>
<head>
  <title>Add part page</title>
</head>
<p><a href='repair_form.php'><button>Go to repair form page</button></a></p>
<body>
    <h2>Add a part</h2>
    <div class="part_details">
        <form name="addPart" action="" method="post">
        <label for="VIN">VIN</label>
        <input type="text" name="VIN" value="<?php echo $VIN;?>"><br><br>
        <label for="startDate">Start date</label>
        <input type="text" name="startDate" value="<?php echo $startDate;?>"><br><br> 
        <label for="partID">PartID*</label>
        <input type="text" name="partID" required><br><br>
        <label for="partQuantity">Part quantity*</label>
        <input type="number" name="partQuantity" required><br><br>
        <label for="partPrice">Part price*</label>
        <input type="number" step=0.01 name="partPrice" required><br><br>
        <label for="vendor">Vendor*</label>
        <input type="text" name="vendor" required><br><br>
        <input type="submit" name="addPart" value="Add part">
        
        </form>
    </div>
</body>
</html>

<?php

function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

if (isset($_POST['addPart'])) {
    $VIN = $_POST["VIN"];
    $startDate = $_POST["startDate"];
    $partID = test_input($_POST["partID"]);
    $vendor = test_input($_POST["vendor"]);
    $partQuantity = $_POST["partQuantity"];
    $partPrice = $_POST["partPrice"];


    if (empty($partID)) {
      array_push($error_msg,  "Error: You must enter a partID ");
      echo "You must enter a partID!<br> ";
    }
    if (empty($vendor)) {
      array_push($error_msg,  "Error: You must enter a vendor ");
      echo "You must enter a vendor!<br> ";
    }
    if (empty($partPrice)) {
      array_push($error_msg,  "Error: You must enter a part price ");
      echo "You must enter a part price!<br> ";
    }
    if (empty($partQuantity)) {
      array_push($error_msg,  "Error: You must enter a part quantity ");
      echo "You must enter a part quantity! <br>";
    }

    if (!empty($partID) and !empty($vendor) and !empty($partPrice) and !empty($partQuantity)) {
        $query_insert = <<<EOT
        INSERT INTO Part (repairID, partID, vendor, part_price, part_quantity) 
        VALUES ((SELECT repairID FROM Repair as R WHERE R.VIN = '{$VIN}' and R.start_date = '{$startDate}'), '{$partID}', '{$vendor}', {$partPrice}, {$partQuantity})
        EOT;
        $result_insert = mysqli_query($db, $query_insert);
    }

    echo "<hr>";
    echo "<h3>Added part Information</h3>";
    echo "<table>";
    $first_row = <<<EOT
    <tr>
        <td class="item_label">VIN</td>
        <td class="item_label">Start Date</td>
        <td class="item_label">PartID</td>
        <td class="item_label">Vendor</td>
        <td class="item_label">Part quantity</td>
        <td class="item_label">Part price</td>  
    </tr>
    EOT;
    echo $first_row;

    $tmp_row = <<<EOT
    <tr>
        <td>{$VIN}</td>
        <td>{$startDate}</td>
        <td>{$partID}</td>
        <td>{$vendor}</td>
        <td>{$partQuantity}</td>
        <td>{$partPrice}</td>  
    </tr>
    EOT;
    echo $tmp_row;
    echo "</table>";


}


?>




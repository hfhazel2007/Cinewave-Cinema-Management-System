<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $hall_id = intval($_GET['id']);
} else {
    $hall_id = 1; 
}

?>            
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Hall Edit</title>
    </head>
<body>
<h1>Hall Edit</h1> 
<?php
if(isset($_POST['update'])){
        $hall_number=$_POST['hall_number'];
        $capacity=$_POST['capacity'];
        $hall_status=$_POST['hall_status'];  
        $address=$_POST['address'];
        $sql_cinema="SELECT cinema_id FROM cinema WHERE address='$address'";
        $result_cinema = $pdo->query($sql_cinema);
        $row_cinema=$result_cinema->fetch(PDO::FETCH_ASSOC);
        $cinema_id=$row_cinema['cinema_id'];         
        $sql="UPDATE hall SET hall_number='$hall_number',capacity='$capacity',hall_status='$hall_status',cinema_id='$cinema_id' where hall_id=$hall_id";
        if($pdo->query($sql) === TRUE){
            header("Location: hall_management.php");
            exit();
        }else {
            echo "Error: " . $pdo->$error;
    }}
$sql= "SELECT * FROM hall WHERE hall_id = $hall_id";
$result = $pdo->query($sql);
$row=$result->fetch(PDO::FETCH_ASSOC);

?>
    
 <form action="hall_edit.php?id=<?php echo $hall_id; ?>" method="post">
    Hall Number:<br>
    <input type="text" name="hall_number" value="<?php echo $row['hall_number']; ?>" required>
    <br><br>

    Capacity:<br>
    <input type="text" name="capacity" value="<?php echo $row['capacity']; ?>" required>
     <br><br>

    Hall Status<br>
    <select name="hall_status" required>
        <option value="Available">Available</option>
        <option value="Unavailable">Unavailable</option>
    </select>
    <br><br>
   
    Branch:<br>
    <select name="address" required>
    <?php
        $sql_cinema="SELECT address FROM cinema ";
        $result_cinema = $pdo->query($sql_cinema);
        while($row_cinema=$result_cinema->fetch(PDO::FETCH_ASSOC)){
    ?>
    <option  value="<?php echo$row_cinema['address']?>"><?php echo$row_cinema['address']?></option>
    <?php
    }?>
    </select>

    <input type="submit" name="update" value="Update">
</form>

</body>
</html>

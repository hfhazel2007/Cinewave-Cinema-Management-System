<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
?>            
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Add Hall </title>
    </head>
<body>
<h1>Add Hall</h1> 
<?php
if(isset($_POST['submit'])){
        $hall_number=$_POST['hall_number'];
        $capacity=$_POST['capacity'];
        $hall_status=$_POST['hall_status']; 
        $address=$_POST['address'];
        $sql_cinema="SELECT cinema_id FROM cinema WHERE address='$address'";
        $result_cinema = $pdo->query($sql_cinema);
        $row_cinema=$result_cinema->fetch(PDO::FETCH_ASSOC);
        $cinema_id=$row_cinema['cinema_id'];           
        $sql="INSERT INTO hall(hall_number,capacity,hall_status,cinema_id) VALUES('$hall_number','$capacity','$hall_status','$cinema_id')";
        if($pdo->query($sql) === TRUE){
            header("Location: hall_management.php");
            exit();
        }else {
            echo "Error: " . $pdo->$error;
    }}

?>
    
 <form action="hall_edit.php" method="post">
    Hall Number:<br>
    <input type="text" name="hall_number"  required>
    <br><br>

    Capacity:<br>
    <input type="text" name="capacity"  required>
     <br><br>

    Hall Status<br>
       <select name="hall_status" required>
        <option value="Available">Available</option>
        <option value="Under Maintanence">Under Maintainence</option>
    </select>
    <br><br>
    
     Branch:<br>
    <?php
        $sql_cinema="SELECT address FROM cinema ";
        $result_cinema = $pdo->query($sql_cinema);
        while($row_cinema=$result_cinema->fetch(PDO::FETCH_ASSOC)){
    ?>
    <input type="radio" name="address" value="<?php echo$row_cinema['address']?>" required><?php echo$row_cinema['address']?>
    <?php
    }?>
    <br><br>
    <input type="submit" name="submit" value="Submit">
</form>

</body>
</html>
             
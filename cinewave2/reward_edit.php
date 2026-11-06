<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();

$id = $_SESSION['id'];
if (isset($_GET['id'])) {
    $reward_id = intval($_GET['id']);
} else {
    $reward_id = 1; 
}

?>            
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Reward Edit</title>
    </head>
<body>
<h1>Reward Edit</h1> 
<?php
if(isset($_POST['update'])){
        $reward_name=$_POST['reward_name'];
        $points_required=$_POST['points_required'];
        $exp_date=$_POST['exp_date'];           
        $sql="UPDATE reward SET reward_name='$reward_name',points_required='$points_required',exp_date='$exp_date' ,staff_id='$id' where reward_id=$reward_id";
        if($pdo->query($sql) === TRUE){
            header("Location: reward_management.php");
            exit();
        }else {
            echo "Error: " . $pdo->$error;
    }}
$sql= "SELECT * FROM reward WHERE reward_id = $reward_id";
$result = $pdo->query($sql);
$row= $result->fetchAll(PDO::FETCH_ASSOC);

?>
    
 <form action="reward_edit.php?id=<?php echo $reward_id; ?>" method="post">
    Name:<br>
    <input type="text" name="reward_name" value="<?php echo $row['reward_name'];?>" required>
    <br><br>

    Points_required:<br>
    <input type="text" name="points_required"  value="<?php echo $row['points_required'];?>"required>
     <br><br>

    Expiry date<br>
    <input type=date name="exp_date" value="<?php echo $row['exp_date'];?>" required>
    <br><br>
   
    <input type="submit" name="update" value="Update">
</form>

</body>
</html>
             
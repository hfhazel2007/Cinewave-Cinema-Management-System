<?php 
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/header.php';
$pdo = getDBConnection();
if (isset($_GET['id'])) {
    $promotion_id = intval($_GET['id']);
} else {
    $promotion_id = 1; 
}

?>            
<!DOCTYPE html>
<html>
    <head>
        <title>CineWave-Promotion Edit</title>
    </head>
<body>
<h1>Promotion Edit</h1>
<?php
if(isset($_POST['update'])){
        $promotion_name=$_POST['promotion_name'];
        $discount_percentage=$_POST['discount_percentage'];
        $conditions=$_POST['conditions'];
        $current_pic_sql = "SELECT path FROM promotion WHERE promotion_id = $promotion_id";
        $current_pic_res = $pdo->query($current_pic_sql);
        $current_row     = $current_pic_res->fetch(PDO::FETCH_ASSOC);
        $path            = $current_row['path'];

        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
            $file= $_FILES['image'];
            $filename= $file['name'];
            $filetemp= $file['tmp_name'];
            $fileError= $file['error'];
            $fileExt =strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            if($fileExt=='png'){
                    $target="asset/promotion/";
                    $relative_path= $target.$filename;
                    
                if(move_uploaded_file($filetemp,$relative_path)){
                    $path=$relative_path;
                   
                }else {
                echo "<p style='color:red;'>Error: Failed to move uploaded file. Check directory write permissions.</p>";
            }
        }}
        
    
 
                    
        $sql="UPDATE promotion SET promotion_name='$promotion_name',discount_percentage='$discount_percentage',conditions='$conditions' ,path='$path' where promotion_id=$promotion_id";
        if($pdo->query($sql) === TRUE){
            header("Location: promotion_management.php");
            exit();
        }else {
            echo "Error: " . $pdo->$error;
    }}
$sql= "SELECT * FROM promotion WHERE promotion_id = $promotion_id";
$result = $pdo->query($sql);
$row=$result->fetch(PDO::FETCH_ASSOC);
?>
    
 <form action="promotion_edit.php?id=<?php echo $promotion_id; ?>" method="post" enctype="multipart/form-data">
     Promotion Name:<br>
    <input type="text" name="promotion_name" value="<?php echo $row['promotion_name']; ?>" required>
    <br><br>

    Discount Percentage:<br>
    <input type="text" name="discount_percentage" value="<?php echo $row['discount_percentage']; ?>" required>
     <br><br>

    Photo:<br>
    <input type="file" name="image" id="imageSelect" accept=".png" required>
    <br><br>
    

    Conditions<br>
    <input type="text" name="conditions" value="<?php echo $row['conditions']; ?>" required>
     <br><br>

    <input type="submit" name="update" value="Update">
</form>


</body>
</html>
             
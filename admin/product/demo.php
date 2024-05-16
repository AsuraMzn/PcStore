<?php
require_once('../classes/Master.php');
require_once('../classes/DBConnection.php');
require_once('../classes/SystemSettings.php');

$Master = new Master();
$action =!isset($_GET['f'])? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();

if ($action == 'save_product') {
    extract($_POST);
    $data = "";
    foreach ($_POST as $k => $v) {
        if (!is_array($_POST[$k]) &&!is_numeric($v)) {
            $_POST[$k] = $Master->conn->real_escape_string($v);
        } elseif (is_array($_POST[$k])) {
            foreach ($_POST[$k] as $kk => $vv) {
                if (!is_numeric($vv))
                    $_POST[$k][$kk] = $Master->conn->real_escape_string($vv);
            }
        }
    }
    extract($_POST);
    $data = "";
    foreach ($_POST as $k => $v) {
        if (!in_array($k, array('id', 'description')) &&!is_array($_POST[$k])) {
            if (!empty($data)) $data.= ",";
            $v = addslashes($v);
            $data.= " `{$k}`='{$v}' ";
        }
    }
    $check = $Master->conn->query("SELECT * FROM `products` where `name` = '{$name}' ".(!empty($id)? " and id!= {$id} " : "")." ")->num_rows;
    if ($Master->capture_err())
        return $Master->capture_err();
    if ($check > 0) {
        $resp['status'] = 'failed';
        $resp['msg'] = "Product Name already exist.";
        echo json_encode($resp);
        exit;
    }
    if (empty($id)) {
        $sql = "INSERT INTO `products` set {$data} ";
    } else {
        $sql = "UPDATE `products` set {$data} where id = '{$id}' ";
    }
    $save = $Master->conn->query($sql);
    if ($save) {
        $pid = empty($id)? $Master->conn->insert_id : $id;
        $upload_path = "uploads/product_". $pid;
        if (!is_dir(base_app. $upload_path))
            mkdir(base_app. $upload_path);
        if (isset($_FILES['img']) && count($_FILES['img']['tmp_name']) > 0) {
            $err = "";
            foreach ($_FILES['img']['tmp_name'] as $k => $v) {
                if (!empty($_FILES['img']['tmp_name'][$k])) {
                    $accept = array('image/jpeg', 'image/png');
                    if (!in_array($_FILES['img']['type'][$k], $accept)) {
                        $err = "Image file type is invalid";
                        break;
                    }
                    if ($_FILES['img']['type'][$k] == 'image/jpeg')
                        $uploadfile = imagecreatefromjpeg($_FILES['img']['tmp_name'][$k]);
                    elseif ($_FILES['img']['type'][$k] == 'image/png')
                        $uploadfile = imagecreatefrompng($_FILES['img']['tmp_name'][$k]);
                    if (!$uploadfile) {
                        $err = "Image is invalid";
                        break;
                    }
                    $temp = imagescale($uploadfile, 400, 400);
                    if ($_FILES['img']['type'][$k] == 'image/jpeg')
                        imagejpeg($temp, base_app. $upload_path. '/'. $_FILES['img']['name'][$k]);
                    elseif ($_FILES['img']['type'][$k] == 'image/png')
                        imagepng($temp, base_app. $upload_path. '/'. $_FILES['img']['name'][$k]);

                    imagedestroy($temp);
                }
            }
            if (!empty($err)) {
                $resp['status'] = 'failed';
                $resp['msg'] = 'Product successfully saved but '.$err;
                $resp['id'] = $pid;
            }
        }
        if (!isset($resp)) {
            $data2 = "";
            $err = "";
            $Master->conn->query("DELETE FROM `specification_list` where product_id = '{$pid}' ");
            if (isset($specs)) {
                foreach ($specs as $k => $v) {
                    if (!empty($data2)) $data2.= ", ";
                    $data2.= "('{$pid}', {$k}, '{$v}')";
                }
            }
            if (!empty($data2)) {
                $sql2 = "INSERT INTO `specification_list` (`product_id`, `meta_field`, `meta_value`) VALUES {$data2}";
                try {
                    $save2= $Master->conn->query($sql2);
                } catch (Exception $e) {
                    $err = $e->getMessage();
                }
            }
            if (empty($err)) {
                $resp['status'] = 'success';
                if (empty($id))
                    $sysset->set_flashdata('success', "New Product successfully saved.");
                else
                    $sysset->set_flashdata('success', "Product successfully updated.");
            } else {
                $resp['status'] = 'success';
                $resp['failed'] = $err;
            }
        }
    } else {
        $resp['status'] = 'failed';
        $resp['err'] = $Master->conn->error. "[{$sql}]";
    }
    echo json_encode($resp);
}

$conn->close();

for ($i=0; $i < 50; $i++) {
    $name = "Product ".$i;
    $description = "This is product ".$i;
    $price = rand(10,100);
    $image = "product_image_".$i.".jpg";
    $specs = array(
        "Processor" => "Intel Core i7",
        "RAM" => "16GB",
        "Storage" => "512GB SSD",
        "Display" => "15.6 inch FHD IPS",
        "GPU" => "NVIDIA GeForce GTX 1650",
        "Battery Life" => "6 hours",
        "Weight" => "4.5 lbs"
    );
    $status = 1;

    $_POST = array(
        "name" => $name,
        "description" => $description,
        "price" => $price,
        "img" => array(
            "name" => $image,
            "type" => "image/jpeg",
            "tmp_name" => "/path/to/product_image_".$i.".jpg",
            "error" => 0,
            "size" => filesize("/path/to/product_image_".$i.".jpg")
        ),
        "specs" => $specs,
        "status" => $status
    );

    $result = $Master->save_product();

    if ($result['status'] == 'success') {
        echo "Product ".$i." added successfully.<br>";
    } else {
        echo "Error adding product ".$i.": ".$result['msg']."<br>";
    }
}
?>
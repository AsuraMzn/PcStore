<?php 
$brands = isset($_GET['b']) ? json_decode(urldecode($_GET['b'])) : array();
?>
<section class="py-0">
    <div id="carouselExampleIndicators" class="carousel slide carousel-fade" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
            <?php 
                $upload_path = "uploads/banner";
                if(is_dir(base_app.$upload_path)): 
                $file= scandir(base_app.$upload_path);
                $_i = 0;
                    foreach($file as $img):
                        if(in_array($img,array('.','..')))
                            continue;
                $_i++;
            ?>
            <div class="carousel-item <?php echo $_i == 1 ? "active" : '' ?>">
                <div class="overlay-image"  style="background-image: url(<?php echo validate_image($upload_path.'/'.$img) ?>);background-position: center;background-size: cover;"></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
</section>
<section>
    <div class="container px-4 px-lg-5 mt-5">
        <div class="row py-5">
            <h1 class="text-center">Popular</h1>
        </div>
        <div class="row gx-4 gx-lg-4 row-cols-md-3 row-cols-xl-4 ">
            <?php 
                $where = "";
                if(count($brands)>0)
                $where = " and p.brand_id in (".implode(",",$brands).") " ;
                $products = $conn->query("SELECT p.*,b.name as bname FROM `products` p inner join brands b on p.brand_id = b.id where p.status = 1 {$where} order by rand() ");
                while($row = $products->fetch_assoc()):
                    $upload_path = base_app.'/uploads/product_'.$row['id'];
                    $img = "";
                    if(is_dir($upload_path)){
                        $fileO = scandir($upload_path);
                        if(isset($fileO[2]))
                            $img = "uploads/product_".$row['id']."/".$fileO[2];
                        // var_dump($fileO);
                    }
                    foreach($row as $k=> $v){
                        $row[$k] = trim(stripslashes($v));
                    }
                    $inventory = $conn->query("SELECT * FROM inventory where product_id = ".$row['id']);
            ?>
            <div class="col-4 pb-5">
                <a class="card c-card product-item text-dark" href=".?p=view_product&id=<?php echo md5($row['id']) ?>">
                    <img src="<?php echo validate_image($img) ?>" class="card-img-top" alt="...">
                    <div class="card-body">
                        <h5 class="fw-bolder"><?php echo $row['name'] ?></h5>
                        <span><b>Price: </b><?php echo number_format($row['price'],2) ?></span>
                        <p class="m-0"><small><b>Brand:</b> <?php echo $row['bname'] ?></small></p>
                    </div>
                    
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<script>
    function _filter(){
        var brands = []
            $('.brand-item:checked').each(function(){
                brands.push($(this).val())
            })
        _b = JSON.stringify(brands)
        var checked = $('.brand-item:checked').length
        var total = $('.brand-item').length
        if(checked == total)
            location.href="./?";
        else
            location.href="./?b="+encodeURI(_b);
    }
    function check_filter(){
        var checked = $('.brand-item:checked').length
        var total = $('.brand-item').length
        if(checked == total){
            $('#brandAll').attr('checked',true)
        }else{
            $('#brandAll').attr('checked',false)
        }
        if('<?php echo isset($_GET['b']) ?>' == '')
            $('#brandAll,.brand-item').attr('checked',true)
    }
    $(function(){
        check_filter()
        $('#brandAll').change(function(){
            if($(this).is(':checked') == true){
                $('.brand-item').attr('checked',true)
            }else{
                $('.brand-item').attr('checked',false)
            }
            _filter()
        })
        $('.brand-item').change(function(){
            _filter()
        })
    })

</script>
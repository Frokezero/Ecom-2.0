<?php
$root=dirname(__DIR__);
if(is_file($root.'/assets/js/navigation.js'))throw new RuntimeException('AJAX page navigation must remain disabled');
foreach(['includes/footer.php','includes/admin_footer.php'] as $file){
    $source=file_get_contents($root.'/'.$file);
    if(str_contains($source,'navigation.js'))throw new RuntimeException('AJAX navigation is still loaded by '.$file);
}
foreach(glob($root.'/assets/js/*.js')?:[] as $file)if(str_contains(file_get_contents($file),'ajax:page-loaded'))throw new RuntimeException('Legacy AJAX page event remains in '.basename($file));
echo "Standard navigation tests passed\n";

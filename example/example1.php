<?php
    require_once __DIR__ . '/../ofx.php';
    use \ofxphp\ofx;
    
    $a=new ofx('./../example.ofx');
    var_dump($a->signonmsgsrsv1());
    echo '<br><br>';
    var_dump($a->account());
    echo '<br><br>';
    var_dump($a->movements());
    echo '<br><br>';
    var_dump($a->balance());
    echo '<br><br>';
?>

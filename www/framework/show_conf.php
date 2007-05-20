<?php
define("IS_IN_CONTOOL", true);

require_once('lib/lib_global.php');

if (!php_sapi_name() == "cli") {
?>
<body>
    <pre style="font: sans-serif; font-size: 12px; line-height: 17px;"><?php
}

function pray ($data, $functions = 0){ 
    if($functions != 0){
        $sf = 1;
    }else{
        $sf = 0 ;
    } // This kluge seemed necessary on one server.
    if (isset ($data)){
        if (is_array($data) || is_object($data)){
            if (count ($data)){
                echo("<OL>\n");
                while (list ($key, $value) = each ($data)){
                    $type = gettype($value);
                    if ($type == "array" || $type == "object"){
                        printf ("<li>(%s) <b>%s</b>:\n", $type, $key);
                        pray ($value, $sf);
                    } else if (eregi ("function", $type)) {
                        if ($sf){
                            printf ("<li>(%s) <b>%s</b> </LI>\n", $type, $key, $value); 
                            // There doesn't seem to be anything traversable inside functions.
                        }
                    } else {
                        if (!$value){
                            $value = "(none)";
                        }
                        printf ("<li>(%s) <b>%s</b> = %s</LI>\n", $type, $key, $value);
                    }
                }
                echo "</OL>end.\n";
            }else{
                echo "(empty)";
            }
        }
    }
} // function
    
if (php_sapi_name() == "cli") {
    print_r($conf);
} else {
    pray($conf, true);
?></pre>
</body><?php
}
?>

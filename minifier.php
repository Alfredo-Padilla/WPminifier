
<?php
/**
* Plugin Name: Minifier
* Description: Plug-in to minimize files from the WordPress interface.
* Version: 2.0
* Author: Alfredo Padilla
**/

// INTERFACE
function minifier_menu_2(){    
    $page_title = 'fminifier';   
    $menu_title = 'FMinifier';   
    $capability = 'manage_options';   
    $menu_slug  = 'minifier';   
    $function   = 'minifier_menu_html';   
    $icon_url   = 'dashicons-media-code';   
    $position   = 4;    
    add_menu_page( 
        $page_title,                  
        $menu_title,                   
        $capability,                  
        $menu_slug,                   
        $function,                   
        $icon_url,                   
        $position 
    ); 
}
add_action( 'admin_menu', 'minifier_menu_2' );
if( !function_exists("minifier_menu_html") ) { 
    function minifier_menu_html() {
        ?>
        <div style="max-width: 1140px; margin: 0 auto;">
            <style>
                table {
                    border: none;
                    border-collapse: collapse;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                td {
                    /* border-bottom: 1px solid lightgray; */
                    padding: 10px 30px 10px 30px;
                    background: white;
                    border-bottom: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                tr:nth-child(even) td {
                    background: #f9f9f9;
                }
                input {
                    color: #0071a1;
                    border: 1px solid #0071a1;
                    border-radius: 3px;
                    
                    background: #f3f5f6;
                    padding: 5px 15px;
                    cursor: pointer;
                }
                input:hover {
                    background-color: #f1f1f1;
                }
                .log-box {
                    margin-top: 50px !important; 
                    background-color: white; 
                    /* max-width: 500px;  */
                    max-height: 500px; 
                    overflow-x: hidden;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                .log-box h1 {
                    background: #f9f9f9;
                    border-bottom: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    text-align: center;
                }
            </style>
            <h1 >Minificador de Archivos</h1>

            <table style="margin: 0 auto;  width: 100%;">
                <?php display_files(get_files()); ?>            
            </table>

            <form style="margin-top: 50px;" method="post"> 
                <input type="submit" name="minify_all" value="Minify all "/>
                <input type="submit" name="delete_all" value="Delete minified" />
            </form>
        <?php

        if (isset($_POST["minify_all"])) {
            ?>
                <div class="log-box" style="margin: 0 auto;">
                    <div style="position: sticky; top: 0; left: 0; background: white;">
                        <h1 style="padding: 10px 0; margin: 0;">Logs</h1>
                    </div>
            <?php
            minimize(get_files());
            echo "</div>";
        }
        if (isset($_POST["delete_all"])) {
            echo "<div class=\"log-box\">";
            ?>
                <div style="position: sticky; top: 0; left: 0; background: white;">
                    <h1 style="padding: 10px 0; margin: 0;">Logs</h1>
                </div>
            <?php
            delete_all_minified(get_files());
            echo "</div>";
        }
        ?> </div> <?php
    }
}
function display_files($files) {
    foreach($files as $file) 
        if (is_array($file)) 
            display_files($files=$file);
        else {
            $fname = explode("/", $file);
            $fname = $fname[sizeof($fname) - 1];

            $extensions = Array("css", "js");
            check_extensions($fname, $extensions, "display_file");
        }   
}

function display_file($file) {
    ?>
        <tr>
            <td>
                <?php echo $file; ?>
            </td>

            <td>
                <form method="post"> 
                    <input style="float: right;" type="submit" name="minify" value="Minify" />
                </form>
            </td>
        </tr>

    <?php
    //echo "<tr><td>$file</td></tr>";
}

function get_files($dir=false) {
    $ret = array();

    if ($dir) 
        $file_list = glob($dir."/*");
    else 
        $file_list = glob(get_template_directory()."/*");
        
    foreach($file_list as $file)  {
        //echo "<h2>$file</h2>";
        if (is_dir($file)) 
            array_push($ret, get_files($file));
        else
            array_push($ret, $dir=$file);
    }
        
    return $ret;
}

function minimize($files, $extensions=0) {
    $extensions = Array("css", "js");

    foreach($files as $file)
        if (is_array($file))
            minimize($file, $extension);
        else
            if (is_dir($file))
                minimize($file, $extensions);
            else
                check_extensions($file, $extensions, "write_file");
}

function check_extensions($file, $extensions, $function) {
    foreach($extensions as $extension)
        if (strpos($file, ".".$extension))
            if (!strpos($file, ".min."))
                $function($file);
}

function write_file($fname) {
    $fname = explode(".", $fname);
    $fname = $fname[0].".min.".$fname[1];

    $open = fopen($fname, "w");
    fwrite($open, request($fname));
    echo "<h2>>Escrito: $fname</h2>";
    fclose($open);
}

function request($file) {
    //echo "<h2>$file</h2>";

    // setup the URL and read the JS from a file
    $url = 'https://javascript-minifier.com/raw';
    $txt = file_get_contents($file);

    // init the request, set various options, and send it
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
        CURLOPT_POSTFIELDS => http_build_query([ "input" => $txt ])
    ]);

    $minified = curl_exec($ch);

    // finally, close the request
    curl_close($ch);

    // output the $minified JavaScript
    return $minified;
}

function delete_all_minified($files) {
    foreach($files as $file)
        if (is_array($file))
            delete_all_minified($file);
        else
            if (is_dir($file))
                delete_all_minified($file);
            else
                if (strpos($file, ".min.")) {
                    unlink($file);
                    echo "<h2>>Borrando: $file</h2>";
                }
}

?>

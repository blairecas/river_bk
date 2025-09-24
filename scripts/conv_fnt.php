<?php

    $fnt_height = 6;
    $barr = Array();
    $minasc = 255;
    $maxasc = 0;    

    
function FindFiles ($location='', $fileregex='') 
{
    if (!$location or !is_dir($location) or !$fileregex) {
        return false;
    }
    $matchedfiles = array();
    $all = opendir($location);
    while ($file = readdir($all)) {
        if (is_dir($location.'/'.$file) and $file <> ".." and $file <> ".") {
            $subdir_matches = FindFiles($location.'/'.$file,$fileregex);
            $matchedfiles = array_merge($matchedfiles,$subdir_matches);
            unset($file);
        }
        elseif (!is_dir($location.'/'.$file)) {
            if (preg_match($fileregex,$file)) {
                array_push($matchedfiles,$location.'/'.$file);
            }
        }
    }
    closedir($all);
    unset($all);
    return $matchedfiles;
} 


function ProcessDir ( $dir )
{
    $files = FindFiles ($dir, '/^([fF]|[cC]).*\.([pP][nN][gG])$/');
    $count = count($files);
    for ($i=0; $i < $count; $i++) 
    {
        ProcessFile($files[$i]);
    }
    echo "Dir: $dir, Files: $count\n";
}


function GetLumi ( $rgb )
{
    $r = ($rgb >> 16) & 0xFF;
    $g = ($rgb >> 8) & 0xFF;
    $b = $rgb & 0xFF;
    $lumi = sqrt($r*$r + $g*$g + $b*$b);
    return $lumi;
}


function GetImgByte ( $im, $y )
{
    $x = 0;
    $res = 0;
    for ($i=0; $i<8; $i++, $x++)
    {
        $res = $res >> 1;
        $rgb = imagecolorat($im, $x, $y);
        $lumi = GetLumi( $rgb );
        if ($lumi > 128) $res = $res | 128;
    }
    return $res;
}

function ProcessFile ( $fname )
{
    global  $barr, $minasc, $maxasc, $fnt_height;
    
    $fnamebase = basename($fname);
    $arr1 = explode(".", $fnamebase); 
    $arr2 = explode("_", $arr1[0]);
    $asc = hexdec($arr2[1]);
    if ($asc < $minasc) $minasc = $asc;
    if ($asc > $maxasc) $maxasc = $asc;
    
    $im = imagecreatefrompng($fname);
    $width  = imagesx($im);
    $height = imagesy($im);

    if ($width != 8 || $height != $fnt_height) {
        echo "ERR: $fname, size problems, must be 8x$fnt_height";
        die;
    }
    
    $barr[$asc] = Array();
    
    // construct array
    for ($y = 0; $y < $height; $y++)
    {
        $b_bits = GetImgByte($im, $y);
        $barr[$asc][$y] = $b_bits;
    }
}

function OutputFile ()
{
    global  $fout_cpu, $fout_ppu, $barr, $fnt_height;
    
    ksort($barr);
    fputs($fout_cpu, "Fn6Dat:");
    foreach ($barr as $asc => $bchar)
    {
        for ($y=0; $y<$fnt_height; $y++) {
            if ($y == 0) fputs($fout_cpu, "\t.word\t");
            $b = $bchar[$y];
            $w = $b | ($b << 8);
            fputs($fout_cpu, decoct($w));
            if ($y < ($fnt_height-1)) fputs($fout_cpu, ", ");
            if ($y == ($fnt_height-1)) fputs($fout_cpu, "\n");
        }
    }
/*    
    fputs($fout_ppu, "Fn6Da2:");
    foreach ($barr as $asc => $bchar)
    {
        for ($y=0; $y<$fnt_height; $y++) {
            if ($y == 0) fputs($fout_ppu, "\t.byte\t");
            $b = $bchar[$y];
            fputs($fout_ppu, decoct($b));
            if ($y < ($fnt_height-1)) fputs($fout_ppu, ", ");
            if ($y == ($fnt_height-1)) fputs($fout_ppu, "\n");
        }
    }
*/
}

function OutputAddrArray ()
{
    global  $fout_cpu, $fout_ppu, $minasc, $maxasc, $fnt_height;
    $k=0; $ofs = 0;
    fputs($fout_cpu, "Fn6Ofs:");
    for ($asc=$minasc; $asc<=$maxasc; $asc++)
    {
        if ($k==0) fputs($fout_cpu, "\t.word\t");
        fputs($fout_cpu, decoct($ofs));
        if ($k<7) fputs($fout_cpu, ", ");
        if ($k==7) fputs($fout_cpu, "\n");
        $k++;
        if ($k>=8) $k=0;
        $ofs = $ofs + ($fnt_height*2);
    }
    fputs($fout_cpu, "\n");
/*
    $k=0; $ofs = 0;
    fputs($fout_ppu, "Fn6Of2:");
    for ($asc=$minasc; $asc<=$maxasc; $asc++)
    {
        if ($k==0) fputs($fout_ppu, "\t.word\t");
        fputs($fout_ppu, decoct($ofs));
        if ($k<7) fputs($fout_ppu, ", ");
        if ($k==7) fputs($fout_ppu, "\n");
        $k++;
        if ($k>=8) $k=0;
        $ofs = $ofs + $fnt_height;
    }
    fputs($fout_ppu, "\n");
*/
}

/////////////////////////////////////////////////////

    ProcessDir("./graphics/fnt/");
    $fout_cpu = fopen("./cpu_font.mac", "w");
    // $fout_ppu = fopen("./ppu_font.mac", "w");
    OutputAddrArray();
    OutputFile();
    fclose($fout_cpu);
    // fclose($fout_ppu);
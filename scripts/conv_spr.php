<?php

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
    $files = FindFiles ($dir, '/^([sS]|[cC]).*\.([pP][nN][gG])$/');
    $count = count($files);
    for ($i=0; $i < $count; $i++) 
    {
        ProcessFile($files[$i]);
    }
    echo "Dir: $dir, Files: $count\n";
}

function GetImgTripple ( $im, $bn, $y, $shift )
{
    $dx = imagesx($im);
    $x = $bn * 8;
    $res = 0;
    for ($i=0; $i<8; $i++, $x++)
    {
        $res = $res >> 2;
	$xnew = $x-$shift; 
	if ($xnew < 0) $xnew = $dx + $xnew;
        $rgb = imagecolorat($im, $xnew, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($b > 127) $res = $res | 0b0100000000000000;
        if ($g > 127) $res = $res | 0b1000000000000000;
        if ($r > 127) $res = $res | 0b1100000000000000;
    }
    return $res;
}


function ProcessFile ( $fname )
{
    global  $fout_cpu, $C_SHIFT_MAX;

    $fnamebase = basename($fname);    
    $arr = explode(".", $fnamebase);
    $sname = strtoupper($arr[0]);
    $sprtype = substr($sname, 0, 1);
    // echo "$fname - $sname\n";
    
    $im = imagecreatefrompng($fname);
    $width  = imagesx($im);		//
    $width  = $width & 0xF8;    	// by 8-pix
    $height = imagesy($im);		//
    $byten  = ($width >> 3);	

    for ($shift=0; $shift<=$C_SHIFT_MAX; $shift++)
    {
        $barr = Array();
        $size = 0;
        // construct array
        for ($y = 0; $y < $height; $y++)
        {
	    for ($bn = 0; $bn < $byten; $bn++)
	    {
	        $t = GetImgTripple($im, $bn, $y, $shift);
                $barr[$y][$bn] = $t;
                $size++;
            }
        }

        // out sizes
        // fputs($fout, "\t.WORD\t" . $byten . "., " . $height . ".\n");

	$spostfix = ($sprtype == 'S' ? ''.$shift : '');
    
        // out words array
        fputs($fout_cpu, 'W' . $sname . $spostfix . ":");
        $cn = 0;
        $cmax = 7;
        $dcnt = 0;
        for ($y=0; $y<count($barr); $y++) 
        {
	    $l = count($barr[$y]);
	    for ($k=0; $k<$l; $k++)
	    {
	        if ($cn == 0) fputs($fout_cpu, "\t.word\t"); else fputs($fout_cpu, ", ");
	        $w = $barr[$y][$k];
	        fputs($fout_cpu, decoct($w)); $dcnt++;
	        if ($cn == $cmax) fputs($fout_cpu, "\n");
	        $cn++; if ($cn > $cmax) $cn = 0;
	    }
        }
        if ($cn != 0) fputs($fout_cpu, "\n");

        if ($sprtype != 'S') break;	
    } // for shift
}


// MAIN ///////////////////////////////////////////////////

    // names starting with 's' - will produce shifted array, must have extra 8 pixels on the right

    $C_SHIFT_MAX = 7;

    $fout_cpu = fopen("../graphics/cpu_sprites.mac", "w");
    ProcessDir("../graphics/");
    fclose($fout_cpu);

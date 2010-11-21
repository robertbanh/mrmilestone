<?php
// ==============
// this will hold all definitions
// ==============
$def_removal_threshold = 50;


// =====================
// 
// =====================
function def_count_days($a, $b)
{
	$gd_a = getdate($a);
	$gd_b = getdate($b);

	$a_new = mktime(12, 0, 0, $gd_a['mon'], $gd_a['mday'], $gd_a['year']);
	$b_new = mktime(12, 0, 0, $gd_b['mon'], $gd_b['mday'], $gd_b['year']);

	return round(abs($a_new - $b_new)/86400);
}

// =====================
// adds 100 to the num, which is the next target value
// =====================
function def_fetchNextTarget($num)
{
	if ($num < 100) return '100';
	
	// for numbers under 1000, we'll do milestone at 100
	if ($num < 1000)
	{
		$newNum = strval($num + 100);
		$newNum = substr($newNum, 0, -2); // chop off the ones and tens
		$newNum = $newNum . "00"; // reset the ones and tens
		return $newNum;
	}
	// else milestone will be at 1000
	else
	{
		$newNum = strval($num + 1000);
		$newNum = substr($newNum, 0, -3); // chop off the ones, tens, hundreds
		$newNum = $newNum . "000"; // reset the ones and tens
		return $newNum;
	}
}

?>
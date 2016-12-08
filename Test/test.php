<?php
	include("../Utils/dateUtils.php");

	$dateTool = new dateUtils();
	if ($dateTool->dayOrNight()) {
		echo "YES";
	}else {
		echo "NO";
	}
?>
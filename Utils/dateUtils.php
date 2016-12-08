<?php

date_default_timezone_set("Asia/Shanghai");
	
class dateUtils
{
	public function dayOrNight() 
	{
		$d = date("H:i:sa");

		$result = false;
		if (strcmp($d, "06:00:00") > 0 & strcmp($d, "18:00:00") < 0) {
			# code...
			echo "白天" . "\n";
			$result = true;
		}else {
			echo "黑夜" . "\n";
			$result = false;
		}

		return $result;
	}
}

?>
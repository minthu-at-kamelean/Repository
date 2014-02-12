<?php
        $weekday = date("w") - 1;
        if ($weekday < 0)
        {
            $weekday += 7;
        }
        $thisMonday=date("Y-m-d",time() - $weekday * 86400);
        $lastMonday= date("Y-m-d",strtotime($thisMonday . "-7 days"));
        $nextMonday=date("Y-m-d",strtotime($thisMonday . "+7 days"));

        echo "This week start From : ". $thisMonday . " to ". date("Y-m-d",strtotime($thisMonday . "+6 days")). "<br/>";
        echo "Last week from: ". $lastMonday . " to ". date("Y-m-d",strtotime($lastMonday . "+6 days")). "<br/>";
        echo "Next week: ". $nextMonday . " to ". date("Y-m-d",strtotime($nextMonday . "+6 days")). "<br/>";
?>

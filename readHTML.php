<?php
        $link = "test.html";
        $html = file_get_contents($link);
    
        $dom = new DOMDocument;
        $dom->loadHTML( $html );
        $rows = array();
        $count=0;
        foreach( $dom->getElementsByTagName( 'tr' ) as $tr ) {
            $cells = array();
            foreach( $tr->getElementsByTagName( 'td' ) as $td ) {
                $cells[] = $td->nodeValue;
            }
            $rows[] = $cells;
            $count+=1;
        }
        var_dump($rows);
?>
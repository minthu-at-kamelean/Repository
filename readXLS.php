<?php
  $data = array();
  
  function add_person( $first, $middle, $last, $email )
  {
  global $data;
  
  $data []= array(
  'first' => $first,
  'middle' => $middle,
  'last' => $last,
  'email' => $email 
  );
  }
  
  if ( $_FILES['file']['tmp_name'] )
  {
  $dom = DOMDocument::load( $_FILES['file']['tmp_name'] );
  $rows = $dom->getElementsByTagName( 'Row' );
  $first_row = true;
  foreach ($rows as $row)
  {
  if ( !$first_row )
  {
  $first = "";
  $middle = "";
  $last = "";
  $email = "";
  
  $index = 1;
  $cells = $row->getElementsByTagName( 'Cell' );
  foreach( $cells as $cell )
  { 
  $ind = $cell->getAttribute( 'Index' );
  if ( $ind != null ) $index = $ind;
  
  if ( $index == 1 ) $first = $cell->nodeValue;
  if ( $index == 2 ) $middle = $cell->nodeValue;
  if ( $index == 3 ) $last = $cell->nodeValue;
  if ( $index == 4 ) $email = $cell->nodeValue;
  
  $index += 1;
  }
  add_person( $first, $middle, $last, $email );
  }
  $first_row = false;
  }
  }
  ?>
  <html>
  <body>
  <table>
  <tr>
  <th>First</th>
  <th>Middle</th>
  <th>Last</th>
  <th>Email</th>
  </tr>
  <?php foreach( $data as $row ) { ?>
  <tr>
  <td><?php echo( $row['first'] ); ?></td>
  <td><?php echo( $row['middle'] ); ?></td>
  <td><?php echo( $row['last'] ); ?></td>
  <td><?php echo( $row['email'] ); ?></td>
  </tr>
  <?php } ?>
  </table>
  </body>
  </html>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">\n"
<head>
  <title>A title</title>
</head>
<body>
This is raw text
<?php echo 'some php'; ?>
  <h1>
  <h1><?php echo 'some php'; ?></h1>
  {:yatheme on :}
  foo {:$a->bar:}
  {:$a->bar:} foo bar
  beginning stuff {: illegal instruction:} trailing stuff
  <?php beginning stuff?> {: illegal instruction:}  trailing stuff
   beginning stuff {: illegal instruction:} <?php trailing stuff?>
  <?php beginning stuff?> {: illegal instruction:} <?php trailing stuff?>
<?php echo 'some php'; ?>
<?php echo 'some php' {: A::$b :}; ?>
<?php
  echo "php started with < ? php\\n";
?>
</body>
</html>
  

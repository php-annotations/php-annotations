<?php

## This script generates formatted documentation for the demo, in Wiki format.

header('Content-type: text/plain');

$file = file_get_contents('index.php');

echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";
echo '<tr><td valign="top" width="300">' . "\n";
echo "=== demo/index.php ===\n";

$code = false;

foreach (explode("\n", $file) as $line)
{
  if (substr(ltrim($line),0,2) == '##')
  {
    if ($code)
    {
      echo "}}}\n";
      echo "</td></tr>";
      echo '<tr><td valign="top"><br/>'."\n";
      $code = false;
    }
    echo rtrim(substr(ltrim($line),3))."\n";
  }
  else // it's code
  {
    if (!$code)
    {
      echo '</td><td width="20"></td><td valign="top">'."\n";
      echo "{{{\n";
      $code = true;
    }
    echo $line;
  }
}

echo '</table>';

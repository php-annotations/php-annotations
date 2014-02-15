<?php

## This script generates formatted documentation for the demo, in Wiki format.

header('Content-type: text/plain');

echo '<table border="0" cellpadding="0" cellspacing="0">' . "\n";
echo '<tr><td valign="top" width="300">' . "\n";
echo "<h3>demo/index.php</h3>\n";

$code = false;

foreach (file('index.php') as $line) {
    if (substr(ltrim($line), 0, 2) == '##') {
        if ($code) {
            echo "</pre>\n";
            echo "</td></tr>";
            echo '<tr><td valign="top"><br/>' . "\n";
            $code = false;
        }

        echo htmlspecialchars(rtrim(substr(ltrim($line), 3))) . "\n";
    } else {
        // it's code
        if (!$code) {
            echo '</td><td valign="top">' . "\n";
            echo "<pre>";
            $code = true;
        }

        echo htmlspecialchars($line);
    }
}

echo '</table>';

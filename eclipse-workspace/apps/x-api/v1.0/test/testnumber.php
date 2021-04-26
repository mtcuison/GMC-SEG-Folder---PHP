<?php

echo str_replace(",", "", "5,152.3") * 1;
echo "<br>";
echo floatval(str_replace(",", "", "x"));
echo "<br>";
echo floatval(str_replace(",", "", null));
echo "<br>";
echo floatval(str_replace(",", "", "5152.3"));
echo "<br>";
echo floatval("5");
echo "<br>";

?>
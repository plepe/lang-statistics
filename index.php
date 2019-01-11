<?php
require "conf.php";
require "lib/modulekit/lang/inc/build_statistic.php";
?>
<!DOCTYPE html>
<html>
<head>
  <link rel='stylesheet' href='style.css'>
</head>
<body>
<?php
$languages = json_decode(file_get_contents("lib/modulekit/lang/lang/list.json"), true);
$languages_en = json_decode(file_get_contents("lib/modulekit/lang/lang/en.json"), true);
?>
<table>
  <tr>
    <th>Code</th>
    <th>Language</th>
    <th>Native name</th>
<?php
$total = 0;
foreach ($dirs as $dirId => $dir) {
  $stat[$dirId] = build_statistic($dir);
  $total += $stat[$dirId][''];

  print "    <th>{$dirId} ({$stat[$dirId]['']})</th>\n";
}
print "    <th>Total ({$total})</th>\n";
print "  </tr>";

?>
<!--
Status:
0-15   #FF0033
16-50  #FF7700
51-70  #FFCC00
71-85  #77CC00
86-100 #33CC00
-->

<?php
foreach ($languages as $code => $native_name) {
  $sum = 0;
  foreach ($dirs as $dirId => $dir) {
    $sum += $stat[$dirId][$code] ?? 0;
  }

  if ($sum > 0) {
    print "  <tr>\n";
    print "    <td>{$code}</td>\n";
    print "    <td>" . $languages_en["lang:{$code}"] . "</td>\n";
    print "    <td>" . $languages[$code] . "</td>\n";
    foreach ($dirs as $dirId => $dir) {
      $completeness = ($stat[$dirId][$code] ?? 0) / $stat[$dirId][''];
      printf("    <td>%.1f%%</td>", $completeness * 100);
    }

    $completeness = $sum / $total;
    printf("    <td>%.1f%%</td>", $completeness * 100);
    print "  </tr>\n";
  }
}

print "</table>\n";

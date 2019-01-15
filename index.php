<?php
require "conf.php";
require "modulekit/loader.php";
require "lib/modulekit/lang/inc/build_statistic.php";
?>
<!DOCTYPE html>
<html>
<head>
  <link rel='stylesheet' href='style.css'>
</head>
<body>
<?php
call_hooks('init');
$languages = json_decode(file_get_contents("lib/modulekit/lang/lang/list.json"), true);
$languages_en = json_decode(file_get_contents("lib/modulekit/lang/lang/en.json"), true);

$def = array(
  'code' => array(
    'name' => "Code",
  ),
  'lang' => array(
    'name' => "Language",
  ),
  'native' => array(
    'name' => "Native name",
  ),
);

$data = array();
$sum = array();

foreach ($dirs as $dirId => $dir) {
  $stat[$dirId] = build_statistic($dir['path']);

  $def[$dirId] = array(
    'name' => "{$dir['name']} ({$stat[$dirId]['']})",
    'sortable' => array('type' => 'num'),
    'format' => "{{ {$dirId}|default(0) }}"
  );

  foreach ($stat[$dirId] as $code => $value) {
    if ($code !== '') {
      $data[$code][$dirId] = $value;

      if (!array_key_exists($code, $sum)) {
        $sum[$code] = 0;
      }

      $sum[$code] += $value;
    }
  }

  $total += $stat[$dirId][''];
}

$def['total'] = array(
  'name' => "Total ({$total})",
  'sort' => array('type' => 'num', 'dir' => 'desc'),
);

foreach ($data as $code => $dummy) {
  $data[$code]['code'] = $code;
  $data[$code]['lang'] = $languages_en["lang:{$code}"];
  $data[$code]['native'] = $languages[$code];
  $data[$code]['total'] = $sum[$code];
}

$table = new table($def, $data, array('template_engine' => 'twig'));
print $table->show();

$status_colors = array(
  15 => '#FF0033',
  50 => '#FF7700',
  70 => '#FFCC00',
  85 => '#77CC00',
  100 =>'#33CC00',
);

function get_status_color ($completeness) {
  global $status_colors;

  foreach ($status_colors as $value => $color) {
    if ($completeness * 100 <= $value) {
      return $color;
    }
  }

  return $lastColor;
}

print "<h2>Contributors</h2>\n";
$pwd = getcwd();
foreach ($dirs as $dirId => $dir) {
  print "<h3>{$dir['name']}</h3>\n";
  print "<ul>\n";

  $f = popen("cd " . escapeshellarg($dir['path']) . "; " . escapeshellarg("{$pwd}/git-log-json") . " .", 'r');
  $output = '';
  while ($r = fgets($f)) {
    $output .= $r;
  }
  pclose($f);

  $history = json_decode($output, true);

  $done = array();
  foreach ($history as $commit) {
    $name = $commit['author']['name'];
    if (!in_array($name, $done)) {
      print "  <li>". htmlspecialchars($name). "</li>";
      $done[] = $name;
    }
  }

  print "</ul>\n";
}

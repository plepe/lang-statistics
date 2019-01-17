<?php
require "conf.php";
require "modulekit/loader.php";
require "lib/modulekit/lang/inc/build_statistic.php";
require "vendor/autoload.php";
$Parsedown = new Parsedown();
?>
<!DOCTYPE html>
<html>
<head>
  <?php print modulekit_to_javascript(); /* pass modulekit configuration to JavaScript */ ?>
  <?php print modulekit_include_js(); /* prints all js-includes */ ?>
  <?php print modulekit_include_css(); /* prints all css-includes */ ?>
  <link rel='stylesheet' href='style.css'>
</head>
<body>
<?php
if (file_exists('header.md')) {
  print $Parsedown->text(file_get_contents('header.md'));
}

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
  $max = $stat[$dirId][''];

  $def[$dirId] = array(
    'name' => "{$dir['name']} ({$stat[$dirId]['']})",
    'sortable' => array('type' => 'num', 'dir' => 'desc'),
    'format' => "{{ ({$dirId} / {$max} * 100)|number_format(1) }}%",
    'class' => "cell-{{ ({$dirId} / {$max} * 5.99)|round(0, 'floor') }}",
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
  'format' => "{{ (total / {$total} * 100)|round(0, 'floor') }}%",
    'class' => "cell-{{ (total / {$total} * 5.99)|round(0, 'floor') }}",
);

foreach ($data as $code => $dummy) {
  $data[$code]['code'] = $code;
  $data[$code]['lang'] = $languages_en["lang:{$code}"];
  $data[$code]['native'] = $languages[$code];
  $data[$code]['total'] = $sum[$code];
}

$table = new table($def, $data, array('template_engine' => 'twig'));
print $table->show();

function get_status_color ($completeness) {
  global $status_colors;

  foreach ($status_colors as $value => $color) {
    if ($completeness * 100 <= $value) {
      return $color;
    }
  }

  return $lastColor;
}

$pwd = getcwd();
$done = array();
foreach ($dirs as $dirId => $dir) {
  $f = popen("cd " . escapeshellarg($dir['path']) . "; " . escapeshellarg("{$pwd}/git-log-json") . " .", 'r');
  $output = '';
  while ($r = fgets($f)) {
    $output .= $r;
  }
  pclose($f);

  $history = json_decode($output, true);

  foreach ($history as $commit) {
    $name = $commit['author']['name'];
    if (!in_array($name, $done)) {
      $done[] = $name;
    }
  }
}
natsort($done);

print "<h2>Contributors</h2>\n";
print "<ul>\n";
foreach ($done as $name) {
  print "  <li>". htmlspecialchars($name). "</li>";
}
print "</ul>\n";

if (file_exists('footer.md')) {
  print $Parsedown->text(file_get_contents('footer.md'));
}

print "</body>\n";
print "</html>\n";

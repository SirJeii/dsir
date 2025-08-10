<?php
function xlsx_send(string $filename, array $rows) {
  $tmp = sys_get_temp_dir() . '/xlsx_' . uniqid();
  @mkdir($tmp.'/xl/worksheets', 0777, true);
  @mkdir($tmp.'/_rels', 0777, true);
  @mkdir($tmp.'/xl/_rels', 0777, true);

  $sheetData = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
  $r=1;
  foreach ($rows as $row) {
    $sheetData .= '<row r="'.$r++.'">';
    $c=1;
    foreach ($row as $cell) {
      $v = htmlspecialchars((string)$cell);
      $col = xlsx_col($c++).($r-1);
      $sheetData .= '<c r="'.$col.'" t="inlineStr"><is><t>'.$v.'</t></is></c>';
    }
    $sheetData .= '</row>';
  }
  $sheetData .= '</sheetData></worksheet>';

  file_put_contents($tmp.'/[Content_Types].xml',
'<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');
  file_put_contents($tmp.'/_rels/.rels',
'<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
  file_put_contents($tmp.'/xl/_rels/workbook.xml.rels',
'<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');
  file_put_contents($tmp.'/xl/workbook.xml',
'<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
 xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
  file_put_contents($tmp.'/xl/styles.xml',
'<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"></styleSheet>');
  file_put_contents($tmp.'/xl/worksheets/sheet1.xml', $sheetData);

  $zipPath = tempnam(sys_get_temp_dir(), 'xl');
  $zip = new ZipArchive(); $zip->open($zipPath, ZipArchive::OVERWRITE);
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $file) { $local = substr($file, strlen($tmp)+1); $zip->addFile($file, $local); }
  $zip->close();

  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Content-Length: '.filesize($zipPath));
  readfile($zipPath);
  // cleanup
  @unlink($zipPath);
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $f) { $f->isDir() ? @rmdir($f) : @unlink($f); }
  @rmdir($tmp);
  exit;
}
function xlsx_col($n){ $s=''; while($n>0){ $m=($n-1)%26; $s=chr(65+$m).$s; $n=intval(($n-$m-1)/26);} return $s; }

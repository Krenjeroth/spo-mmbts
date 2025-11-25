<?php
if (!function_exists('word_open_doc')) {
    function word_open_doc(string $docxPath)
    {
        $word = new \COM("Word.Application") or die("Unable to start Word");
        $word->Visible = false;
        $doc  = $word->Documents->Open($docxPath, false, false, false, "", "", true);
        return [$word, $doc];
    }
}

if (!function_exists('word_close')) {
    function word_close($word, $doc)
    {
        $doc->Close(false);
        $word->Quit();
    }
}

if (!function_exists('word_save_pdf')) {
    function word_save_pdf($doc, string $pdfPath) {
      // Make sure path is absolute and uses backslashes
      $absPath = realpath($pdfPath) ?: $pdfPath;
      $absPath = str_replace('/', '\\', $absPath);
      if (preg_match('/^[A-Za-z]:\\\\/', $absPath) === 0) {
          $absPath = getcwd() . '\\' . $absPath;
      }

      try {
          // 17 = wdFormatPDF
          $doc->SaveAs2(new \VARIANT($absPath, VT_BSTR), new \VARIANT(17, VT_I4));
      } catch (\Throwable $e) {
          throw new \Exception("Word SaveAs2 PDF failed for path {$absPath}: ".$e->getMessage());
      }
    }
}

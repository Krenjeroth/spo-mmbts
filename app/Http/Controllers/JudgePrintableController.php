<?php
// app/Http/Controllers/JudgePrintableController.php
namespace App\Http\Controllers;

use App\Models\Judge;
use App\Models\Score;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\CategoryResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CurrentEventService;

class JudgePrintableController extends Controller
{
    public function printPerJudge(Category $category, Judge $judge)
    {
        $eventId  = CurrentEventService::getId();

        $template = resource_path('word/judge_score_summary.docx');
        $docx     = storage_path('app/tmp/' . uniqid('pj_') . '.docx');
        $pdf      = preg_replace('/\.docx$/', '.pdf', $docx);
        @mkdir(dirname($docx), 0777, true);
        copy($template, $docx);

        [$word, $doc] = word_open_doc($docx);

        $replaceAll = function (string $find, string $replace) use ($doc) {
            $wdFindContinue = 1; $wdReplaceAll = 2;
            $doFind = function($range) use ($find,$replace,$wdFindContinue,$wdReplaceAll) {
                $range->Find->ClearFormatting();
                $range->Find->Execute($find,false,false,false,false,false,true,$wdFindContinue,false,$replace,$wdReplaceAll);
            };
            $doFind($doc->Content);
            $story = $doc->StoryRanges(1);
            while ($story) { $doFind($story); $story = $story->NextStoryRange; }
            $sections = $doc->Sections;
            for ($s = 1; $s <= $sections->Count; $s++) {
                $sec = $sections->Item($s);
                foreach (['Headers','Footers'] as $hf) {
                    $col = $sec->$hf;
                    for ($i=1; $i <= $col->Count; $i++) {
                        $rng = $col->Item($i)->Range; $doFind($rng);
                        if (isset($rng->Shapes)) {
                            $sh = $rng->Shapes;
                            for ($k=1; $k <= $sh->Count; $k++) {
                                $shape = $sh->Item($k);
                                if (isset($shape->TextFrame) && $shape->TextFrame->HasText) {
                                    $doFind($shape->TextFrame->TextRange);
                                }
                            }
                        }
                    }
                }
                if (isset($sec->Range->Shapes)) {
                    $bodyShapes = $sec->Range->Shapes;
                    for ($k=1; $k <= $bodyShapes->Count; $k++) {
                        $shape = $bodyShapes->Item($k);
                        if (isset($shape->TextFrame) && $shape->TextFrame->HasText) {
                            $doFind($shape->TextFrame->TextRange);
                        }
                    }
                }
            }
        };

        $replaceAll('{{event_title}}',   strtoupper($category->event->title));
        $replaceAll('{{category_name}}', strtoupper($category->name));
        $replaceAll('{{judge_name}}',    strtoupper(optional($judge->user)->name ?? ('JUDGE '.($judge->judge_number ?? ''))));
        $replaceAll('{{generated_at}}',  now()->format('Y-m-d H:i'));

        $results = CategoryResult::with(['contestant.municipality'])
            ->where('event_id', $eventId)
            ->where('category_id', $category->id)
            ->orderByRaw("CASE WHEN rank IS NULL THEN 1 ELSE 0 END, rank ASC")
            ->get();

        $grouped = $results->map(function ($r) {
                return [
                    'contestant_id'    => $r->contestant_id,
                    'candidate_number' => (string)optional($r->contestant)->number,
                    'municipality'     => (string)optional($r->contestant?->municipality)->name,
                    'gender'           => strtolower(optional($r->contestant)->gender ?? ''),
                    'official_total'   => (float)$r->category_total,
                    'official_rank'    => $r->rank,
                ];
            })
            ->filter(fn ($x) => in_array($x['gender'], ['male','female'], true))
            ->groupBy('gender');

        $criteria = DB::table('criteria')
            ->where('event_id', $eventId)
            ->where('category_id', $category->id)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        if ($criteria->isEmpty()) {
            word_save_pdf($doc, $pdf);
            word_close($word, $doc);
            if (file_exists($docx)) @unlink($docx);
            return response()->download($pdf)->deleteFileAfterSend(true);
        }

        /**
         * Create a brand-new clean table at a bookmark position and return it.
         * We delete any existing table and insert a fresh grid so indices are deterministic.
         */
        $createCleanTableAtBookmark = function($doc, string $bookmark, int $cols, int $rows) {
            if (!$doc->Bookmarks->Exists($bookmark)) {
                throw new \RuntimeException("Bookmark '{$bookmark}' not found.");
            }
            $rng = $doc->Bookmarks($bookmark)->Range;
            // If a table is inside the bookmark, remove it.
            if (isset($rng->Tables) && $rng->Tables->Count >= 1) {
                $old = $rng->Tables(1);
                $rng = $old->Range;
                $rng->Collapse(1); // wdCollapseStart
                try { $old->Delete(); } catch (\Throwable $e) {}
            } else {
                $rng->Collapse(1);
            }

            // Insert a NEW clean table
            $rows = max(3, $rows);               // at least 3 header rows
            $cols = max(6, $cols);               // sanity minimum
            $tbl  = $doc->Tables->Add($rng, $rows, $cols);

            // Basic styling (keep simple; widths applied later)
            for ($i = 1; $i <= 6; $i++) {
                $b = $tbl->Borders->Item($i);
                $b->LineStyle = 1;  // single
                $b->LineWidth = 2;  // ~0.5pt
                $b->Color     = 0;  // auto
            }
            $tbl->TopPadding = 1;  $tbl->BottomPadding = 1;
            $tbl->LeftPadding = 2; $tbl->RightPadding  = 2;

            // Ensure 3 rows for headers
            while ($tbl->Rows->Count < 3) $tbl->Rows->Add();

            return $tbl;
        };

        $getTableByBookmarkOrIndex = function ($doc, string $bookmark, int $indexFallback) {
            if ($doc->Bookmarks->Exists($bookmark)) {
                $rng = $doc->Bookmarks($bookmark)->Range;
                if ($rng->Tables->Count >= 1) return $rng->Tables(1);
            }
            return ($doc->Tables->Count >= $indexFallback) ? $doc->Tables($indexFallback) : null;
        };

        $setTableTypography = function($table, array $opts = []) {
            $fontName   = $opts['font']       ?? 'Calibri';   // why: consistent print
            $bodySize   = (int)($opts['size'] ?? 9);
            $headerSize = (int)($opts['headerSize'] ?? $bodySize);

            // Whole table: base font
            try {
                $rng = $table->Range;
                $rng->Font->Name = $fontName;
                $rng->Font->Size = $bodySize;
                $rng->ParagraphFormat->SpaceBefore = 0;
                $rng->ParagraphFormat->SpaceAfter  = 0;
                $rng->ParagraphFormat->LineSpacingRule = 0; // single
            } catch (\Throwable $e) {}

            // Header row (single-row header layout you chose)
            try {
                $hdr = $table->Rows(1)->Range;
                $hdr->Font->Name = $fontName;
                $hdr->Font->Size = $headerSize;
                $hdr->Font->Bold = 1;
                $hdr->ParagraphFormat->Alignment = 1; // center
                $hdr->ParagraphFormat->SpaceBefore = 0;
                $hdr->ParagraphFormat->SpaceAfter  = 0;
            } catch (\Throwable $e) {}

            // Optional: slightly reduce row padding for compactness
            try {
                $table->TopPadding = 0.5;
                $table->BottomPadding = 0.5;
                $table->LeftPadding = 1.5;
                $table->RightPadding = 1.5;
            } catch (\Throwable $e) {}
        };

        $normalizeBorders = function($table) {
            try { $table->AllowSpacingBetweenCells = false; } catch (\Throwable $e) {}
            try { $table->Spacing = 0; } catch (\Throwable $e) {}
            $table->TopPadding = 1; $table->BottomPadding = 1;
            $table->LeftPadding = 2; $table->RightPadding = 2;
            for ($i = 1; $i <= 6; $i++) {
                $b = $table->Borders->Item($i);
                $b->LineStyle = 1; $b->LineWidth = 2; $b->Color = 0;
            }
        };

        // FINAL, robust header builder
        $writeHeader = function ($table, $criteria) {
            $critCount = max(0, count($criteria));
            $totalCols = 2 + (2 * $critCount) + 2; // #, LGU, RAWxN, %xN, Total, Rank

            // Ensure grid columns and at least 1 header row
            while ($table->Columns->Count < $totalCols) { $table->Columns->Add(); }
            if ($table->Rows->Count < 1) { $table->Rows->Add(); }

            // Normalize row 1 to exactly totalCols visible cells (no merges)
            $normalizeRow = function($row, int $cols) {
                $g = 0;
                while ($row->Cells->Count < $cols && $g++ < 200) {
                    try { $row->Cells($row->Cells->Count)->Split(1, 2); } catch (\Throwable $e) { break; }
                }
                while ($row->Cells->Count > $cols) {
                    try { $row->Cells($row->Cells->Count - 1)->Merge($row->Cells($row->Cells->Count)); } catch (\Throwable $e) { break; }
                }
            };
            $normalizeRow($table->Rows(1), $totalCols);

            // Clear row 1
            for ($c = 1; $c <= $totalCols; $c++) {
                $table->Cell(1,$c)->Range->Text = '';
            }

            // Fixed cells
            $table->Cell(1,1)->Range->Text = '';
            $table->Cell(1,2)->Range->Text = '';

            // Write RAW criteria block (columns 3..2+N)
            for ($i = 0; $i < $critCount; $i++) {
                $label  = trim((string)($criteria[$i]->name ?? ''));
                $weight = isset($criteria[$i]->percentage) ? ' ' . number_format((float)$criteria[$i]->percentage, 0) . '%' : '';
                $table->Cell(1, 3 + $i)->Range->Text = $label . $weight;
            }

            // Write PERCENTAGE criteria block (columns 3+N..2+2N)
            for ($i = 0; $i < $critCount; $i++) {
                $label  = trim((string)($criteria[$i]->name ?? ''));
                $weight = isset($criteria[$i]->percentage) ? ' ' . number_format((float)$criteria[$i]->percentage, 0) . '%' : '';
                $table->Cell(1, 3 + $critCount + $i)->Range->Text = $label . $weight;
            }

            // Last two columns
            $table->Cell(1, $totalCols - 1)->Range->Text = 'Total';
            $table->Cell(1, $totalCols)->Range->Text     = 'Ranking';

            // Minimal formatting
            for ($c = 1; $c <= $totalCols; $c++) {
                $cell = $table->Cell(1,$c);
                $cell->Range->Bold = 1;
                $cell->Range->ParagraphFormat->Alignment = 1; // center
                try { $cell->VerticalAlignment = 1; } catch (\Throwable $e) {}
            }
        };


        // ---- body writer unchanged (kept as-is) ----
        $writeBody = function ($table, $rows, $criteria) use ($eventId, $category, $judge) {
            $critCount = count($criteria);

            // Column blocks
            $rawStart  = 3;
            $pctStart  = $rawStart + $critCount;

            // Single header row
            $startRow = 1;
            $totalCol = (int)$table->Columns->Count - 1;
            $rankCol  = (int)$table->Columns->Count;

            // Cache scores to cut DB calls
            $scoresCache = [];
            foreach ($rows as $row) {
                $cid = $row['contestant_id'];
                foreach ($criteria as $crit) {
                    $scoresCache[$cid][$crit->id] = (float)\App\Models\Score::where('event_id',$eventId)
                        ->where('category_id',$category->id)
                        ->where('contestant_id',$cid)
                        ->where('judge_id',$judge->id)
                        ->where('criterion_id',$crit->id)
                        ->sum('score');
                }
            }

            // Totals for dense ranking
            $totals = [];
            foreach ($rows as $row) {
                $cid = $row['contestant_id'];
                $sum = 0.0;
                foreach ($criteria as $crit) {
                    $raw = $scoresCache[$cid][$crit->id] ?? 0.0;
                    $sum += $raw * ((float)$crit->percentage / 100.0);
                }
                $totals[$cid] = $sum;
            }
            $uniq = array_values(array_unique(array_map(fn($v)=>round($v, 6), $totals)));
            rsort($uniq, SORT_NUMERIC);
            $rankByTotal = []; $rk = 1;
            foreach ($uniq as $v) { $rankByTotal[$v] = $rk++; }

            // Write rows
            $r = $startRow;
            foreach ($rows as $row) {
                $r++;
                while ($table->Rows->Count < $r) { $table->Rows->Add(); }

                $cid = $row['contestant_id'];

                // # column — center the value
                $seqCell = $table->Cell($r,1);
                $seqCell->Range->Text = (string)$row['candidate_number'];
                $seqCell->Range->ParagraphFormat->Alignment = 1; // center

                // LGU / NAME
                $nameCell = $table->Cell($r,2);
                $nameCell->Range->Text = mb_strtoupper($row['municipality']);

                // RAW block (2 decimals)
                $rawVals = [];
                foreach ($criteria as $crit) {
                    $rawVals[] = $scoresCache[$cid][$crit->id] ?? 0.0;
                }
                for ($i=0; $i<$critCount; $i++) {
                    $c = $table->Cell($r, $rawStart + $i);
                    $c->Range->Text = number_format($rawVals[$i] ?? 0, 2);
                    $c->Range->ParagraphFormat->Alignment = 2; // right
                }

                // % block (5 decimals) + total (5 decimals)
                $pctVals = [];
                $judgeTotal = 0.0;
                foreach ($criteria as $crit) {
                    $raw = $scoresCache[$cid][$crit->id] ?? 0.0;
                    $w = $raw * ((float)$crit->percentage / 100.0);
                    $pctVals[] = $w;
                    $judgeTotal += $w;
                }
                for ($i=0; $i<$critCount; $i++) {
                    $c = $table->Cell($r, $pctStart + $i);
                    $c->Range->Text = number_format($pctVals[$i] ?? 0, 5);
                    $c->Range->ParagraphFormat->Alignment = 2; // right
                }

                // Total (5 dp)
                $totCell = $table->Cell($r,$totalCol);
                $totCell->Range->Text = number_format($judgeTotal, 5);
                $totCell->Range->ParagraphFormat->Alignment = 2;

                // Ranking — centered
                $rankKey = round($judgeTotal, 6);
                $rankVal = $rankByTotal[$rankKey] ?? '';
                $rkCell = $table->Cell($r,$rankCol);
                $rkCell->Range->Text = (string)$rankVal;
                $rkCell->Range->ParagraphFormat->Alignment = 1; // center
            }

            // Trim extra rows
            $lastUsed = $r;
            while ($table->Rows->Count > $lastUsed) {
                try { $table->Rows($table->Rows->Count)->Delete(); } catch (\Throwable $e) { break; }
            }

            // Keep compact padding
            $table->TopPadding = 1;  $table->BottomPadding = 1;
            $table->LeftPadding = 2; $table->RightPadding  = 2;
        };

        // ===== Fill Ms & Mr =====
        $critCount = $criteria->count();
        $colsNeeded = 2 + (2 * $critCount) + 2; // #, LGU, RAWxN, %xN, Total, Rank

        // FEMALE
        if ($doc->Bookmarks->Exists('TABLE_FEMALE')) {
            $femaleCount = (int) $grouped->get('female', collect())->count();
            $rowsNeeded  = max(1 + $femaleCount, 8); // single header row + data
            $tblFemale   = $createCleanTableAtBookmark($doc, 'TABLE_FEMALE', $colsNeeded, $rowsNeeded);
            $writeHeader($tblFemale, $criteria);
            $writeBody($tblFemale, $grouped->get('female', collect()), $criteria);
            $normalizeBorders($tblFemale);
            $setTableTypography($tblFemale, ['size'=>9, 'headerSize'=>9, 'font'=>'Calibri']); // ← make smaller
        }

        // MALE
        if ($doc->Bookmarks->Exists('TABLE_MALE')) {
            $maleCount = (int) $grouped->get('male', collect())->count();
            $rowsNeeded  = max(1 + $maleCount, 8);
            $tblMale     = $createCleanTableAtBookmark($doc, 'TABLE_MALE', $colsNeeded, $rowsNeeded);
            $writeHeader($tblMale, $criteria);
            $writeBody($tblMale, $grouped->get('male', collect()), $criteria);
            $normalizeBorders($tblMale);
            $setTableTypography($tblMale, ['size'=>9, 'headerSize'=>9, 'font'=>'Calibri']);    // ← make smaller
        }

        // Signature block (unchanged)
        $anchor = $doc->Content;
        // if ($tableMale)      $anchor = $tableMale->Range;
        // elseif ($tableFemale)$anchor = $tableFemale->Range;
        $anchor->SetRange($anchor->End, $anchor->End);
        $anchor->InsertAfter("\r");
        $sp = $anchor->Paragraphs->Last->Range;
        $sp->Text = chr(160);
        $sp->ParagraphFormat->LineSpacingRule = 4;
        $sp->ParagraphFormat->LineSpacing     = 28;
        $sp->ParagraphFormat->SpaceBefore     = 0;
        $sp->ParagraphFormat->SpaceAfter      = 0;

        $after = $sp; $after->SetRange($after->End, $after->End);
        $sigTable = $doc->Tables->Add($after, 2, 1);
        $sigTable->AutoFitBehavior(2);
        for ($b=1; $b<=6; $b++) { $sigTable->Borders->Item($b)->LineStyle = 0; }
        $sigTable->Cell(1,1)->Range->Text = str_repeat('_', 32);
        $sigTable->Cell(1,1)->Range->ParagraphFormat->Alignment = 1;
        $sigTable->Cell(1,1)->Range->ParagraphFormat->SpaceAfter = 1;
        $sigTable->Cell(2,1)->Range->Text =
            (optional($judge->user)->name ? optional($judge->user)->name . "\r" : '') .
            'Judge ' . (int)($judge->judge_number ?? 1);
        $sigTable->Cell(2,1)->Range->ParagraphFormat->Alignment = 1;

        word_save_pdf($doc, $pdf);
        word_close($word, $doc);
        if (file_exists($docx)) @unlink($docx);

        return response()->download($pdf)->deleteFileAfterSend(true);
    }
}

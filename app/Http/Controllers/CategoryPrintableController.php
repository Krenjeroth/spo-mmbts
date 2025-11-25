<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CurrentEventService;
use Illuminate\Support\Facades\DB;
use App\Models\Score;
use App\Models\Contestant;
use App\Models\Judge;

class CategoryPrintableController extends Controller
{
    public function printCategory(Category $category) {
        $eventId = CurrentEventService::getId();

        // your template (already designed)
        $template = resource_path('word/category_summary.docx');
        $docx = storage_path('app/tmp/' . uniqid('cat_') . '.docx');
        $pdf  = preg_replace('/\.docx$/', '.pdf', $docx);
        @mkdir(dirname($docx), 0777, true);
        copy($template, $docx);

        // open Word COM
        [$word, $doc] = word_open_doc($docx);

        // simple placeholder replacements (header text)
        $replaceAll = function (string $find, string $replace) use ($doc) {
            $wdFindContinue = 1; // wrap
            $wdReplaceAll   = 2;

            // Helper: run Find on a given Range
            $doFind = function ($range) use ($find, $replace, $wdFindContinue, $wdReplaceAll) {
                $range->Find->ClearFormatting();
                $range->Find->Execute(
                    $find,      // FindText
                    false,      // MatchCase
                    false,      // MatchWholeWord
                    false,      // MatchWildcards
                    false,      // MatchSoundsLike
                    false,      // MatchAllWordForms
                    true,       // Forward
                    $wdFindContinue,
                    false,      // Format
                    $replace,   // ReplaceWith
                    $wdReplaceAll
                );
            };

            // 1) Main document content
            $doFind($doc->Content);

            // 2) All story ranges (covers headers/footers, footnotes, text frames, etc.)
            $story = $doc->StoryRanges(1); // wdMainTextStory is typically 1
            while ($story) {
                $doFind($story);
                // Some stories contain linked text frames; traverse them via NextStoryRange
                $story = $story->NextStoryRange;
            }

            // 3) Shapes with TextFrames in headers/footers and body
            $sections = $doc->Sections;
            for ($s = 1; $s <= $sections->Count; $s++) {
                $sec = $sections->Item($s);

                // Headers & Footers
                foreach (['Headers', 'Footers'] as $hf) {
                    $col = $sec->$hf;
                    for ($i = 1; $i <= $col->Count; $i++) {
                        $rng = $col->Item($i)->Range;
                        // direct range
                        $doFind($rng);

                        // shapes inside
                        $shapes = $rng->ShapeRange ?? null;
                        if ($shapes) {
                            for ($k = 1; $k <= $shapes->Count; $k++) {
                                $shape = $shapes->Item($k);
                                if (isset($shape->TextFrame) && $shape->TextFrame->HasText) {
                                    $doFind($shape->TextFrame->TextRange);
                                }
                            }
                        }
                        // also iterate Shapes collection
                        if (isset($rng->Shapes)) {
                            $sh = $rng->Shapes;
                            for ($k = 1; $k <= $sh->Count; $k++) {
                                $shape = $sh->Item($k);
                                if (isset($shape->TextFrame) && $shape->TextFrame->HasText) {
                                    $doFind($shape->TextFrame->TextRange);
                                }
                            }
                        }
                    }
                }

                // Body shapes (if template has text boxes in the main page body)
                if (isset($sec->Range->Shapes)) {
                    $bodyShapes = $sec->Range->Shapes;
                    for ($k = 1; $k <= $bodyShapes->Count; $k++) {
                        $shape = $bodyShapes->Item($k);
                        if (isset($shape->TextFrame) && $shape->TextFrame->HasText) {
                            $doFind($shape->TextFrame->TextRange);
                        }
                    }
                }
            }
        };

        $replaceAll('{{event_title}}', strtoupper($category->event->title));
        $replaceAll('{{category_name}}', strtoupper($category->name));
        $replaceAll('{{generated_at}}', now()->format('Y-m-d H:i'));

        // Judges (kept your query)
        $judges = Judge::with('user')
            ->whereHas('phases', fn($q) => $q->where('phase_id', $category->phase_id))
            ->get()
            ->map(fn($j) => (object)[
                'id'   => $j->id,
                'name' => $j->user->name,
            ]);

        // Get contestants + their per-judge totals (kept your logic)
        $contestants = Contestant::with(['municipality', 'event'])->where('event_id', $eventId)->orderBy('number', 'asc')->get(['id', 'name', 'gender', 'number', 'municipality_id', 'event_id']);

        $judges = Judge::with('user')
        ->where('event_id', $eventId)
        ->where('is_active', true)
        ->whereHas('phases', fn($q) => $q->where('phase_id', $category->phase_id))
        ->orderByRaw('CASE WHEN judge_number IS NULL THEN 1 ELSE 0 END, judge_number ASC, id ASC')
        ->get()
        ->map(function ($j, $idx) {
            // Use judge_number if set; else 1-based index
            $n = $j->judge_number ?? ($idx + 1);
            return (object)[
                'id'           => $j->id,
                'name'         => optional($j->user)->name ?? "Judge {$n}",
                'judge_number' => $n,
            ];
        });

        $rows = [];
        foreach ($contestants as $c) {
            $line = [
                'candidate_number' => (string) $c->number,
                'municipality'     => (string) optional($c->municipality)->name,
                'gender'           => strtolower($c->gender),
            ];
            $sum = 0;
            foreach ($judges as $j) {
                $score = (float) Score::where('event_id', $eventId)
                    ->where('category_id', $category->id)
                    ->where('contestant_id', $c->id)
                    ->where('judge_id', $j->id)
                    ->whereHas('criterion', fn($q) => $q->whereNull('parent_id'))
                    ->sum('weighted_score');
                $line['judge_' . $j->id] = $score;
                $sum += $score;
            }
            $avg = count($judges) ? $sum / count($judges) : 0;
            $line['average'] = $avg;
            $line['total']   = $avg * ($category->weight / 100);
            $rows[] = $line;
        }

        // rank per gender (kept)
        $grouped = collect($rows)->groupBy('gender')->map(function ($g) {
            $ranked = $g->sortByDesc('total')->values()->map(function ($r, $i) {
                $r['rank'] = $i + 1;
                return $r;
            });

            return $ranked->sortBy(fn($r) => (int)$r['candidate_number'])->values();
        });

        // ---------- helpers for dynamic judge columns ----------
        // Ensure the table has exactly (5 base + N judge) columns. Insert judge columns after 'Candidate'.
        $ensureJudgeColumns = function ($table, int $needed) {
            // Base template should have 5 columns: SEQ(1), Candidate(2), Average(3), Total(4), Rank(5)
            $currentTotalCols = $table->Columns->Count;              // could already be > 5 if template has placeholders
            $currentJudgeCols = max(0, $currentTotalCols - 5);

            if ($currentJudgeCols < $needed) {
                // Insert missing judge columns BEFORE current col 3 (Average)
                $insertBeforeCol = 3;
                for ($i = 0; $i < ($needed - $currentJudgeCols); $i++) {
                    $table->Columns->Add($table->Columns($insertBeforeCol));
                }
            } elseif ($currentJudgeCols > $needed) {
                // Remove extra judge columns starting at first judge column (3)
                for ($i = 0; $i < ($currentJudgeCols - $needed); $i++) {
                    $table->Columns(3)->Delete();
                }
            }

            // After reshape: total columns = 5 + needed
            $firstJudgeCol = 3;
            $avgCol        = 3 + $needed;
            $totalCol      = 4 + $needed;
            $rankCol       = 5 + $needed;

            return [$firstJudgeCol, $avgCol, $totalCol, $rankCol];
        };

        $formatTable = function($table, int $judgeCount, int $firstJudgeCol, int $avgCol, int $totalCol, int $rankCol) use ($word) {
            // Let Word size the table to the page width
            // 2 = wdAutoFitWindow
            $table->AutoFitBehavior(2);

            // Extra: turn on AutoFit for columns too (prevents clipping when many judges)
            // (Some Word versions need both)
            if (method_exists($table->Columns, 'AutoFit')) {
                $table->Columns->AutoFit();
            }

            // Reduce cell padding to squeeze a bit more when judges are many
            // Values are points; keep small
            $table->TopPadding    = 1;
            $table->BottomPadding = 1;
            $table->LeftPadding   = 2;
            $table->RightPadding  = 2;

            // Alignments: center for SEQ/Rank, left for Candidate, right for numeric columns
            // 0=left, 1=center, 2=right
            // Guard for column count since we don't know template shape at runtime
            $colCount = $table->Columns->Count;

            if ($colCount >= 1) { $table->Columns(1)->Select(); $word->Selection->ParagraphFormat->Alignment = 1; } // SEQ center
            if ($colCount >= 2) { $table->Columns(2)->Select(); $word->Selection->ParagraphFormat->Alignment = 0; } // Candidate left

            // Judges + Average + Total → right align
            for ($ci = $firstJudgeCol; $ci <= min($avgCol, $colCount); $ci++) {
                $table->Columns($ci)->Select();
                $word->Selection->ParagraphFormat->Alignment = 2; // right
            }

            if ($colCount >= $totalCol) {
                $table->Columns($totalCol)->Select();
                $word->Selection->ParagraphFormat->Alignment = 2; // right
            }

            if ($colCount >= $rankCol) {
                $table->Columns($rankCol)->Select();
                $word->Selection->ParagraphFormat->Alignment = 1; // center
            }

            // Make header row bold & centered (row 1)
            if ($table->Rows->Count >= 1) {
                $table->Rows(1)->Range->Bold = 1;
                $table->Rows(1)->Range->ParagraphFormat->Alignment = 1;
            }
        };

        $writeHeader = function ($table, $judges, int $firstJudgeCol, int $avgCol, int $totalCol, int $rankCol) {
            // Row 1 is the header row in this simplified template
            // Guard: ensure at least one row exists
            if ($table->Rows->Count < 1) { $table->Rows->Add(); }

            $table->Cell(1, 1)->Range->Text = '';
            $table->Cell(1, 2)->Range->Text = '';

            $col = $firstJudgeCol;
            $idx = 1;
            // foreach ($judges as $j) {
            //     $name = trim((string)$j->name);
            //     if ($name !== '' && stripos($name, 'judge') === 0) {
            //         $hdr = $name;                 // "Judge 1"
            //     } elseif ($name !== '') {
            //         $hdr = "Judge {$idx} - {$name}";  // ASCII hyphen avoids encoding issues
            //     } else {
            //         $hdr = "Judge {$idx}";
            //     }
            //     $table->Cell(1, $col++)->Range->Text = $hdr;
            //     $idx++;
            // }

            foreach ($judges as $_) {
                $table->Cell(1, $col++)->Range->Text = "Judge {$idx}";
                $idx++;
            }

            $table->Cell(1, $avgCol)->Range->Text   = 'Average';
            $table->Cell(1, $totalCol)->Range->Text = 'Total (%)';
            $table->Cell(1, $rankCol)->Range->Text  = 'Rank';
        };

        $writeBody = function ($table, $rows, $judges, int $firstJudgeCol, int $avgCol, int $totalCol, int $rankCol) {
            $r = 2;
            foreach ($rows as $row) {
                while ($table->Rows->Count < $r) { $table->Rows->Add(); }

                // Column 1: candidate number (sequence)
                $table->Cell($r, 1)->Range->Text = (string) $row['candidate_number'];

                // Column 2: municipality (replacing candidate name)
                $table->Cell($r, 2)->Range->Text = (string) $row['municipality'];

                // per-judge columns
                $col = $firstJudgeCol;
                foreach ($judges as $j) {
                    $val = $row['judge_' . $j->id] ?? 0;
                    $table->Cell($r, $col++)->Range->Text = number_format($val, 5, '.', '');
                }

                $table->Cell($r, $avgCol)->Range->Text   = number_format($row['average'], 5, '.', '');
        $table->Cell($r, $totalCol)->Range->Text = number_format($row['total'],   5, '.', '');
                $table->Cell($r, $rankCol)->Range->Text  = (string) $row['rank'];

                $r++;
            }
        };

        // Helper to fetch table via bookmark if present, else fallback by index
        $getTableByBookmarkOrIndex = function ($doc, string $bookmark, int $indexFallback) {
            if ($doc->Bookmarks->Exists($bookmark)) {
                $rng = $doc->Bookmarks($bookmark)->Range;
                if ($rng->Tables->Count >= 1) {
                    return $rng->Tables(1);
                }
            }
            // fallback: whole document table by index
            return ($doc->Tables->Count >= $indexFallback) ? $doc->Tables($indexFallback) : null;
        };

        // ---------- locate tables (bookmark first, then fallback) ----------
        $tableFemale = $getTableByBookmarkOrIndex($doc, 'TABLE_FEMALE', 1);
        $tableMale   = $getTableByBookmarkOrIndex($doc, 'TABLE_MALE',   2);

        if ($tableFemale) {
            [$firstJudgeCol, $avgCol, $totalCol, $rankCol] = $ensureJudgeColumns($tableFemale, count($judges));
            $writeHeader($tableFemale, $judges, $firstJudgeCol, $avgCol, $totalCol, $rankCol);
            $writeBody($tableFemale, $grouped['female'] ?? [], $judges, $firstJudgeCol, $avgCol, $totalCol, $rankCol);
            $formatTable($tableFemale, count($judges), $firstJudgeCol, $avgCol, $totalCol, $rankCol);
        }

        if ($tableMale) {
            [$firstJudgeCol, $avgCol, $totalCol, $rankCol] = $ensureJudgeColumns($tableMale, count($judges));
            $writeHeader($tableMale, $judges, $firstJudgeCol, $avgCol, $totalCol, $rankCol);
            $writeBody($tableMale, $grouped['male'] ?? [], $judges, $firstJudgeCol, $avgCol, $totalCol, $rankCol);
            $formatTable($tableMale, count($judges), $firstJudgeCol, $avgCol, $totalCol, $rankCol);
        }
        
        $anchor = $doc->Content;
        if (isset($tableMale) && $tableMale) {
            $anchor = $tableMale->Range;
        } elseif (isset($tableFemale) && $tableFemale) {
            $anchor = $tableFemale->Range;
        }
        $anchor->SetRange($anchor->End, $anchor->End);

        $judgeCount = count($judges);
        if ($judgeCount > 0) {
            $anchor->InsertAfter("\r");                                  // make a new paragraph
            $spacer = $anchor->Paragraphs->Last->Range;
            $spacer->Text = chr(160);                                    // NBSP so it exists

            // wdLineSpaceExactly = 4
            $spacer->ParagraphFormat->LineSpacingRule = 4;               // exact line spacing
            $spacer->ParagraphFormat->LineSpacing     = 30;              // points (try 24–36)
            $spacer->ParagraphFormat->SpaceBefore     = 0;               // no extra
            $spacer->ParagraphFormat->SpaceAfter      = 0;

            // prepare a fresh range *after* the spacer
            $afterSpacer = $spacer;
            $afterSpacer->SetRange($afterSpacer->End, $afterSpacer->End);

            // ---------- now add the signature table at `afterSpacer` ----------
            $sigTable = $doc->Tables->Add($afterSpacer, 2, $judgeCount);
            $sigTable->AutoFitBehavior(2); // wdAutoFitWindow
            $sigTable->TopPadding = 2;  $sigTable->BottomPadding = 2;
            $sigTable->LeftPadding = 4; $sigTable->RightPadding = 4;

            // nuke all borders (table/rows/cols/cells) to avoid vertical ticks
            for ($b = 1; $b <= 6; $b++) { $sigTable->Borders->Item($b)->LineStyle = 0; }

            // ---- fill each column (underscore line + name/label) ----
            for ($col = 1; $col <= $judgeCount; $col++) {
                $j = $judges[$col - 1];
                $label = 'Judge ' . (int)$j->judge_number;
                $name  = trim((string)($j->name ?? ''));

                // Row 1: shorter underscore line
                $c1 = $sigTable->Cell(1, $col);
                $c1->Range->Text = str_repeat('_', 22);
                $c1->Range->ParagraphFormat->Alignment = 1;
                $c1->Range->Font->Size = 11;
                $c1->Range->ParagraphFormat->SpaceBefore = 0;
                $c1->Range->ParagraphFormat->SpaceAfter  = 1;

                // Row 2: Name + label
                $c2 = $sigTable->Cell(2, $col);
                $c2->Range->Text = '';
                $c2->Range->ParagraphFormat->Alignment = 1;
                $c2->Range->ParagraphFormat->SpaceBefore = 0;
                $c2->Range->ParagraphFormat->SpaceAfter  = 0;

                if ($name !== '' && !preg_match('/^Judge\s*\d+$/i', $name)) {
                    $c2->Range->InsertAfter($name . "\r" . $label);
                    $p = $c2->Range->Paragraphs;
                    if ($p->Count >= 1) { $p->Item(1)->Range->Bold = 1; $p->Item(1)->Range->Font->Size = 10; }
                    if ($p->Count >= 2) { $p->Item(2)->Range->Bold = 0; $p->Item(2)->Range->Font->Size = 9; }
                } else {
                    $c2->Range->InsertAfter($name === '' ? $label : $name);
                    $c2->Range->Font->Size = 10;
                }
            }
            $sigTable->Range->Font->Size = 10;
        }

        // save + close
        word_save_pdf($doc, $pdf);
        word_close($word, $doc);

        if (file_exists($docx)) {
            @unlink($docx); // cleanup temp .docx
        }

        return response()->download($pdf)->deleteFileAfterSend(true);
    }
}

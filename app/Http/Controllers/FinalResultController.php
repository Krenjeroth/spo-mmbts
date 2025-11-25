<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CurrentEventService;
use App\Models\Score;
use App\Models\Category;
use Illuminate\Support\Str;

class FinalResultController extends Controller
{
    public function index(Request $request) {
        $eventId = $request->query('event_id') ?? CurrentEventService::getId();

        // Fetch all categories for the event
        $categories = Category::where('event_id', $eventId)->get();
        $qaCategory = $categories->firstWhere('name', 'Q & A');

        // Get all scores (with contestant, category, and criteria)
        $scores = Score::with([
            'contestant:id,name,gender',
            'criterion.category:id,name,weight,event_id'
        ])
            ->where('event_id', $eventId)
            ->get();

        // Group scores by contestant
        $groupedByContestant = $scores->groupBy('contestant_id');
        $results = [];

        foreach ($groupedByContestant as $contestantId => $contestantScores) {
            $contestant = $contestantScores->first()->contestant;

            $categoryTotals = [];
            foreach ($contestantScores->groupBy('criterion.category_id') as $categoryId => $scoresInCategory) {
                $category = $scoresInCategory->first()->criterion->category;

                // Compute weighted category score
                $average = $scoresInCategory->avg('score');
                $weighted = $average * ($category->weight / 100);

                $categoryTotals[$category->name] = [
                    'category_id' => $categoryId,
                    'name' => $category->name,
                    'average' => round($average, 2),
                    'weighted' => round($weighted, 2),
                ];
            }

            // Separate QA vs overall
            $qaScore = 0;
            $overallScore = 0;

            foreach ($categoryTotals as $cat) {
                if ($qaCategory && $cat['category_id'] == $qaCategory->id) {
                    $qaScore = $cat['average'];
                } else {
                    $overallScore += $cat['weighted'];
                }
            }

            $results[] = [
                'contestant_id' => $contestantId,
                'name' => $contestant->name,
                'gender' => strtolower($contestant->gender),
                'overall_total' => round($overallScore, 2),
                'qa_total' => round($qaScore, 2),
            ];
        }

        // Separate by gender
        $groupedByGender = collect($results)->groupBy('gender');

        $finalRankings = collect();
        $top5ByGender = [];

        foreach ($groupedByGender as $gender => $genderResults) {
            // Sort by overall_total (pre-QA) to get gender-specific top 5
            $sortedByOverall = $genderResults->sortByDesc('overall_total')->values();
            $top5 = $sortedByOverall->take(5);
            $top5ByGender[$gender] = $top5;

            // Compute grand totals per gender
            $genderFinal = $genderResults->map(function ($item) use ($top5) {
                $isFinalist = $top5->pluck('contestant_id')->contains($item['contestant_id']);
                $qaWeight = 0.40;
                $overallWeight = 0.60;

                $grandTotal = ($item['overall_total'] * $overallWeight)
                            + (($isFinalist ? $item['qa_total'] : 0) * $qaWeight);

                return array_merge($item, [
                    'is_finalist' => $isFinalist,
                    'grand_total' => number_format($grandTotal, 5, '.', ''),
                ]);
            })->sortByDesc('grand_total')->values();

            // Add gender-specific ranking
            $genderFinal = $genderFinal->values()->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            $finalRankings = $finalRankings->merge($genderFinal);
        }

        return response()->json([
            'data' => [
                'top5' => $top5ByGender,
                'final_rankings' => $finalRankings->values(),
            ],
        ]);
    }

    public function printFinalResult(Request $request) {
        $eventId = $request->query('event_id') ?? CurrentEventService::getId();

        // ---------- 1) Recompute final rankings (same logic as index) ----------
        $categories = Category::with('event')
            ->where('event_id', $eventId)
            ->get();

        $qaCategory = $categories->firstWhere('name', 'Q & A');

        $scores = Score::with([
            'contestant:id,name,gender,number,municipality_id,event_id',
            'contestant.municipality:id,name',
            'criterion.category:id,name,weight,event_id',
        ])
            ->where('event_id', $eventId)
            ->get();

        // group scores by contestant
        $groupedByContestant = $scores->groupBy('contestant_id');
        $results = [];

        foreach ($groupedByContestant as $contestantId => $contestantScores) {
            /** @var \App\Models\Contestant $contestant */
            $contestant = $contestantScores->first()->contestant;

            $categoryTotals = [];
            foreach ($contestantScores->groupBy('criterion.category_id') as $categoryId => $scoresInCategory) {
                $category = $scoresInCategory->first()->criterion->category;

                $average  = $scoresInCategory->avg('score');
                $weighted = $average * ($category->weight / 100);

                $categoryTotals[$category->name] = [
                    'category_id' => $categoryId,
                    'name'        => $category->name,
                    'average'     => round($average, 2),
                    'weighted'    => round($weighted, 2),
                ];
            }

            // separate QA vs overall
            $qaScore      = 0;
            $overallScore = 0;

            foreach ($categoryTotals as $cat) {
                if ($qaCategory && $cat['category_id'] == $qaCategory->id) {
                    $qaScore = $cat['average'];
                } else {
                    $overallScore += $cat['weighted'];
                }
            }

            $results[] = [
                'contestant_id'   => $contestantId,
                'name'            => $contestant->name,
                'gender'          => strtolower($contestant->gender),
                'overall_total'   => round($overallScore, 2),
                'qa_total'        => round($qaScore, 2),
                'candidate_number'=> $contestant->number,
                'municipality'    => optional($contestant->municipality)->name,
            ];
        }

        // group by gender
        $groupedByGender = collect($results)->groupBy('gender');

        $finalRankings = collect();

        foreach ($groupedByGender as $gender => $genderResults) {
            // top 5 per gender based on overall_total
            $sortedByOverall = $genderResults->sortByDesc('overall_total')->values();
            $top5            = $sortedByOverall->take(5);

            $genderFinal = $genderResults->map(function ($item) use ($top5) {
                $isFinalist    = $top5->pluck('contestant_id')->contains($item['contestant_id']);
                $qaWeight      = 0.40;
                $overallWeight = 0.60;

                $grandTotal = ($item['overall_total'] * $overallWeight)
                            + (($isFinalist ? $item['qa_total'] : 0) * $qaWeight);

                return array_merge($item, [
                    'is_finalist' => $isFinalist,
                    'grand_total' => number_format($grandTotal, 5, '.', ''),
                ]);
            })
            ->sortByDesc('grand_total')
            ->values();

            // apply ranks per gender
            $genderFinal = $genderFinal->values()->map(function ($item, $index) {
                $item['rank'] = $index + 1;
                return $item;
            });

            $finalRankings = $finalRankings->merge($genderFinal);
        }

        // now split back by gender for printing, and sort each by candidate_number
        $rowsByGender = $finalRankings
            ->groupBy('gender')
            ->map(function ($items) {
                return $items
                    ->sortBy(function ($r) {
                        return (int)($r['candidate_number'] ?? 0);
                    })
                    ->values()
                    ->map(function ($r) {
                        return [
                            'candidate_number' => (string)($r['candidate_number'] ?? ''),
                            'municipality'     => (string)($r['municipality'] ?? ''),
                            'grand_total' => number_format($r['grand_total'], 5, '.', ''),
                            'rank'             => (int)$r['rank'],
                        ];
                    });
            });

        $femaleRows = $rowsByGender['female'] ?? collect();
        $maleRows   = $rowsByGender['male']   ?? collect();

        // ---------- 2) Prepare Word template ----------
        $template = resource_path('word/final_result.docx');
        $docx     = storage_path('app/tmp/' . uniqid('final_result_') . '.docx');
        $pdf      = preg_replace('/\.docx$/', '.pdf', $docx);

        @mkdir(dirname($docx), 0777, true);
        copy($template, $docx);

        [$word, $doc] = word_open_doc($docx);

        // --- helper to replace placeholders everywhere ---
        $replaceAll = function (string $find, string $replace) use ($doc) {
            $wdFindContinue = 1;
            $wdReplaceAll   = 2;

            $doFind = function ($range) use ($find, $replace, $wdFindContinue, $wdReplaceAll) {
                $range->Find->ClearFormatting();
                $range->Find->Execute(
                    $find,
                    false,
                    false,
                    false,
                    false,
                    false,
                    true,
                    $wdFindContinue,
                    false,
                    $replace,
                    $wdReplaceAll
                );
            };

            $doFind($doc->Content);

            $story = $doc->StoryRanges(1);
            while ($story) {
                $doFind($story);
                $story = $story->NextStoryRange;
            }

            $sections = $doc->Sections;
            for ($s = 1; $s <= $sections->Count; $s++) {
                $sec = $sections->Item($s);
                foreach (['Headers', 'Footers'] as $hf) {
                    $col = $sec->$hf;
                    for ($i = 1; $i <= $col->Count; $i++) {
                        $rng = $col->Item($i)->Range;
                        $doFind($rng);
                    }
                }
            }
        };

        // Event title (grab from any category->event or fallback)
        $eventTitle = optional($categories->first()->event)->title ?? 'Final Results';
        $replaceAll('{{event_title}}', strtoupper($eventTitle));
        $replaceAll('{{generated_at}}', now()->format('Y-m-d H:i'));

        // ---------- 3) Word table helpers ----------
        $getTableByBookmarkOrIndex = function ($doc, string $bookmark, int $indexFallback) {
            if ($doc->Bookmarks->Exists($bookmark)) {
                $rng = $doc->Bookmarks($bookmark)->Range;
                if ($rng->Tables->Count >= 1) {
                    return $rng->Tables(1);
                }
            }
            return ($doc->Tables->Count >= $indexFallback) ? $doc->Tables($indexFallback) : null;
        };

        $writeHeader = function ($table) {
            if ($table->Rows->Count < 1) {
                $table->Rows->Add();
            }

            $table->Cell(1, 1)->Range->Text = 'No.';
            $table->Cell(1, 2)->Range->Text = 'Municipality';
            $table->Cell(1, 3)->Range->Text = 'Grand Total Score';
            $table->Cell(1, 4)->Range->Text = 'Rank';
        };

        $writeBody = function ($table, $rows) {
            $r = 2;
            foreach ($rows as $row) {
                while ($table->Rows->Count < $r) {
                    $table->Rows->Add();
                }

                $table->Cell($r, 1)->Range->Text = (string) $row['candidate_number'];
                $table->Cell($r, 2)->Range->Text = (string) $row['municipality'];
                $table->Cell($r, 3)->Range->Text = number_format($row['grand_total'], 5, '.', '');
                $table->Cell($r, 4)->Range->Text = (string) $row['rank'];

                $r++;
            }
        };

        $formatTable = function ($table) use ($word) {
            // autofit to window
            $table->AutoFitBehavior(2);

            $colCount = $table->Columns->Count;

            // 1=wdAlignParagraphCenter, 2=right, 0=left
            if ($colCount >= 1) { $table->Columns(1)->Select(); $word->Selection->ParagraphFormat->Alignment = 1; } // No. center
            if ($colCount >= 2) { $table->Columns(2)->Select(); $word->Selection->ParagraphFormat->Alignment = 0; } // Municipality left
            if ($colCount >= 3) { $table->Columns(3)->Select(); $word->Selection->ParagraphFormat->Alignment = 2; } // Grand total right
            if ($colCount >= 4) { $table->Columns(4)->Select(); $word->Selection->ParagraphFormat->Alignment = 1; } // Rank center

            // header row bold + center
            if ($table->Rows->Count >= 1) {
                $table->Rows(1)->Range->Bold = 1;
                $table->Rows(1)->Range->ParagraphFormat->Alignment = 1;
            }
        };

        // ---------- 4) Locate tables and fill them ----------
        $tableFemale = $getTableByBookmarkOrIndex($doc, 'TABLE_FEMALE', 1);
        $tableMale   = $getTableByBookmarkOrIndex($doc, 'TABLE_MALE',   2);

        if ($tableFemale) {
            $writeHeader($tableFemale);
            $writeBody($tableFemale, $femaleRows);
            $formatTable($tableFemale);
        }

        if ($tableMale) {
            $writeHeader($tableMale);
            $writeBody($tableMale, $maleRows);
            $formatTable($tableMale);
        }

        // ---------- 5) Save as PDF + cleanup ----------
        word_save_pdf($doc, $pdf);
        word_close($word, $doc);

        if (file_exists($docx)) {
            @unlink($docx);
        }

        return response()->download($pdf)->deleteFileAfterSend(true);
    }

}

<?php

namespace App\Actions\Export;

use App\Models\Survey;
use App\Models\User;
use App\Models\SurveySession;

use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportSurvey implements FromCollection, WithStyles, WithColumnWidths, WithMapping, WithEvents, WithTitle, ShouldAutoSize, WithHeadings
{
    use Exportable;

    protected $survey;
    protected $users;
    protected $surveySessions;
    protected $row = 1;

    public function __construct($surveyId, $userId)
	{
        $this->survey = Survey::whereId($surveyId)->with(['questions.responses', 'questions.options'])->first();
        $this->sessions = SurveySession::with(['responses' => function ($q) {
                $q->groupBy('survey_session_id');
                $q->groupBy('survey_id');
            }])
            ->whereHas('responses', function ($query) {
                $query->whereSurveyId($this->survey->id);
            })
            ->whereUserId($userId)
            ->get();
	}

    public function collection()
    {
        // return $this->users;
        return $this->sessions;
    }

    public function columnWidths(): array
    {
        $questions = $this->survey->questions->toArray();
        $tableWidths = [];

        $column = 'A';
        foreach ($questions as $question) {
            
            if (in_array($question['type'], ['radio', 'checkbox'])) {
                foreach ($question['options'] as $iOption => $option) {
                    $column++;
                    $tableWidths[$column] = strlen($option['value']);
                }
            } else {
                $column++;
                $tableWidths[$column] = strlen($question['content']);
            }
            
        }
        
        return $tableWidths;
    }

    public function styles(Worksheet $sheet)
    {
        $styleHeader = [
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ],
            'font' => [
                'name'      =>  'Calibri',
                'size'      =>  10,
                'bold'      =>  true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '7d9ec7',
                ],
            ]
        ];

        $styleContent = [
            'font' => [
                'name'      =>  'Calibri',
                'size'      =>  10,
                // 'bold'      =>  true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                ],
            ],
        ];

        // foreach ($this->users as $key => $value) {
            //     $contentStyling[$key+3] = $styleContent;
            // }
            // 3 row for headers
        $contentStyling = [];
        foreach ($this->sessions as $iSession => $session) {
            $contentStyling[$iSession+3] = $styleContent;
        }

        return [
            1 => $styleHeader,
            2 => $styleHeader,
            ...$contentStyling,
        ];
    }

     /**
     * @return array
     */
    public function registerEvents(): array
    {        
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $questions = $this->survey->questions->toArray();
                $totalInRight = $this->survey->total_in_right;
                $totalInBottom = $this->survey->total_in_bottom;
                $workSheet = $event->sheet->getDelegate();

                // Headers
                $event->sheet->mergeCells('A1:A2');
                $alphabet = 'B';
                $countColumnHeader = 0;
                foreach ($questions as $question) {
                    if (in_array($question['type'], ['radio', 'checkbox'])) {
                        $firstColumn = $alphabet . '1';
                        foreach ($question['options'] as $iOption => $option) {
                            if ($iOption !== 0) $alphabet++;
                            $countColumnHeader++;
                        }
                        $lastColumn = $alphabet . '1';

                        $event->sheet->mergeCells($firstColumn . ':' . $lastColumn);
                    } else {
                        $event->sheet->mergeCells($alphabet . '1:' . $alphabet. '2');
                        $countColumnHeader++; 
                    }

                    $alphabet++;
                }

                if ($totalInRight) {
                    $event->sheet->mergeCells($alphabet . '1:' . $alphabet. '2');
                    $countColumnHeader++;
                }

                // Freeze Header
                $workSheet->freezePaneByColumnAndRow(2,3);

                // Content
                $countStaticColumn = $this->survey->single_survey ? 2 : 1; 
                $firstColumnContent = $this->survey->single_survey ? 'C' : 'B';
                $firstRowContent = 3; // column 1 & 2 for header
                $firstSheetContent = $firstColumnContent . $firstRowContent;

                $row = $firstRowContent;
                $column = $firstColumnContent;
                foreach ($this->sessions as $iSession => $session) {
                    $column = $firstColumnContent;
                    $firstSheet = $column . $row;

                    $row++;
                    for ($iHeader = 0; $iHeader < $countColumnHeader - $countStaticColumn; $iHeader++) { 
                        if ($iHeader === $countColumnHeader - $firstRowContent) $beforeLastSheetColumn = $column . ($firstRowContent + $iSession);
                        
                        if ($totalInBottom && $iSession === count($this->sessions)-1) {
                            $event->sheet->setCellValue($column . $row, '=SUM('.$column.$firstRowContent.':'.$column.($row-1).')');
                        }
                        
                        $column++;
                    }
                    
                    $lastSheetColumn = $column . ($firstRowContent + $iSession);

                    if ($totalInBottom) {
                        $event->sheet->setCellValue($column . $row, '=SUM('.$column.$firstRowContent.':'.$column.($row-1).')');
                        if ($iSession === count($this->sessions)-1)
                            $event->sheet->setCellValue('B' . $row, 'Jumlah');
                    }

                    if ($totalInRight)
                        $event->sheet->setCellValue($lastSheetColumn, '=SUM('.$firstSheet.':'.$beforeLastSheetColumn.')');

                }

                
                $lastColumnContent = $row;
                $lastRowContent = $column;
                $lastSheetContent = $column . $row;

                $styleContent = [
                    'font' => [
                        'name'      =>  'Calibri',
                        'size'      =>  10,
                        // 'bold'      =>  true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'ffff00',
                        ],
                    ]
                ];

                $workSheet->getStyle($firstSheetContent . ':' . $lastSheetContent)
                    ->applyFromArray($styleContent);

            },
        ];
    }

    public function title(): string
    {
        return 'General';
    }

    public function headings(): array
    {
        $firstHeader = ['No.'];
        $secondHeader = [' '];
        $questions = $this->survey->questions->toArray();
        $totalInRight = $this->survey->total_in_right;

        foreach ($questions as $question) {
            if (in_array($question['type'], ['radio', 'checkbox'])) {
                foreach ($question['options'] as $iOption => $option) {
                    if ($iOption === 0) {
                        $firstHeader[] = $question['content'];
                    } else {
                        $firstHeader[] = '';
                    }

                    $secondHeader[] = $option['value'];
                }
            } else {
                $firstHeader[] = $question['content'];
                $secondHeader[] = '';
            }
        }

        if ($totalInRight) {
            $firstHeader[] = 'Jumlah';
            $secondHeader[] = ' ';
        }

        return [$firstHeader, $secondHeader];
    }

    public function map($data): array
    {
        $results = [$this->row];
        $questions = $this->survey->questions->toArray();

        foreach ($questions as $iQuestion => $question) {
                $responseUser = '';
                $responsesUser = [];

                $options = array_column($question['options'], 'value');
                foreach ($question['responses'] as $response) {
                    if ($data->id === $response['survey_session_id']) {
                        if ($options && count($options) > 0) {
                            if ($question['type'] === 'checkbox') {
                                $responsesUser[] = \in_array($response['content'], $options) ? 'Yes' : '';
                            } else {
                                foreach ($options as $option) {
                                    if ($response['content'] === $option) {
                                        $responsesUser[] = 'Yes';        
                                    } else {
                                        $responsesUser[] = '';
                                    }
                                }
                            }
                        } else {
                            $responseUser = $response['content'];
                        }
                    }
                }

                if (count($responsesUser)) {
                    array_push($results, ...$responsesUser);     
                } else {
                    $results[] = $responseUser;
                }
        }

        $this->row++;

        return $results;
    }
    
}

<?php

namespace App\Actions\Export;

use App\Models\Survey;
use App\Models\User;
use App\Models\SurveySession;
use App\Models\Header;

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
    protected $isHeaderLevel3Exist;
    protected $row = 1;

    public function __construct($surveyId, $userId)
	{
        $this->survey = Survey::whereId($surveyId)->with(['questions.responses', 'questions.options', 'headers'])->first();
        $this->isHeaderLevel3Exist = Header::whereSurveyId($this->survey->id)->whereLevel(3)->first();
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

        $contentStyling = [];
        foreach ($this->sessions as $iSession => $session) {
            $contentStyling[$iSession+3] = $styleContent;
        }

        if ($this->isHeaderLevel3Exist)
            return [
                1 => $styleHeader,
                2 => $styleHeader,
                3 => $styleHeader,
                ...$contentStyling,
            ];    

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
                $averageInRight = $this->survey->average_in_right;
                $averageInBottom = $this->survey->average_in_bottom;
                $isSingleSurvey = $this->survey->single_survey;
                
                $workSheet = $event->sheet->getDelegate();
                $urlCells = [];
                $headerLevel2 = [];
                $headerLevel3 = [];
                $columnHeaderLevel2 = [];
                $columnHeaderLevel3 = [];
                
                // Listing custom header level 2
                foreach ($this->survey->headers as $header) {
                    $currentColumns = explode(',', $header->columns);

                    if ($header->level === 2) {
                        $headerLevel2[] = $header;
                        $columnHeaderLevel2 = [...$columnHeaderLevel2, ...$currentColumns];
                    }
                    if ($header->level === 3) {
                        $headerLevel3[] = $header;
                        $columnHeaderLevel3 = [...$columnHeaderLevel3, ...$currentColumns];
                    }
                }
                
                // Headers
                // merge cell header column no
                $countRowHeader = count($headerLevel3) ? 3 : 2; 
                $event->sheet->mergeCells('A1:A' . $countRowHeader);
                $alphabet = 'B';
                $countColumnHeader = 0;
                $row = count($headerLevel3) ? 2 : 1;

                foreach ($questions as $question) {
                    $isColumnInCustomHeader2 = in_array($alphabet, $columnHeaderLevel2);
                    $isColumnInCustomHeader3 = in_array($alphabet, $columnHeaderLevel3);
                    
                    if (in_array($question['type'], ['radio', 'checkbox'])) {
                        $workSheet->getColumnDimension($alphabet)->setAutoSize(true);
                        $firstAlphabet = $alphabet;
                        $firstColumn = $alphabet . $row;

                        foreach ($question['options'] as $iOption => $option) {
                            if ($iOption !== 0) $alphabet++;
                            $countColumnHeader++;
                            $workSheet->getColumnDimension($alphabet)->setAutoSize(true);
                        }

                        $lastColumn = $alphabet . $row;

                        if (count($headerLevel3) && !$isColumnInCustomHeader3) {
                            $event->sheet->mergeCells($firstAlphabet . '1:' . $lastColumn);
                        } else {
                            $event->sheet->mergeCells($firstColumn . ':' . $lastColumn);
                        }
                    } else {
                        $workSheet->getColumnDimension($alphabet)->setAutoSize(true);

                        if (!$isColumnInCustomHeader2) {
                            if ($isColumnInCustomHeader3) {
                                $value = $event->sheet->getCell($alphabet . '3')->getValue();

                                $event->sheet->setCellValue($alphabet . '2', $value);
                                $event->sheet->mergeCells($alphabet . '2:' . $alphabet . $countRowHeader);
                            } else {
                                $event->sheet->mergeCells($alphabet . '1:' . $alphabet . $countRowHeader);
                            }
                        }

                        if ($question['type'] === 'file') $urlCells[] = $alphabet;

                        $countColumnHeader++; 
                    }

                    $alphabet++;
                }

                // apply header level 2
                foreach ($headerLevel2 as $header2) {
                    $currentHeader2 = explode(',', $header2->columns);
                    $firstColumn = $currentHeader2[0];
                    $lastColumn = $currentHeader2[count($currentHeader2)-1];

                    $event->sheet->mergeCells($firstColumn . $row . ':' . $lastColumn . $row);
                    $event->sheet->setCellValue($firstColumn . $row, $header2->title);

                    foreach ($currentHeader2 as $header) {
                        $event->sheet->getColumnDimension($header)->setAutoSize(false);
                        $event->sheet->getColumnDimension($header)->setWidth(round(strlen($header2->title) + 1 / count($currentHeader2), 0) * 0.5);
                    }
                }

                // apply header level 3
                foreach ($headerLevel3 as $header3) {
                    $currentHeader3 = explode(',', $header3->columns);
                    $firstColumn = $currentHeader3[0];
                    $lastColumn = $currentHeader3[count($currentHeader3)-1];

                    $event->sheet->mergeCells($firstColumn . '1:' . $lastColumn . '1');
                    $event->sheet->setCellValue($firstColumn . '1', $header3->title);
                }

                if ($totalInRight || $averageInRight) {
                    $event->sheet->mergeCells($alphabet . '1:' . $alphabet. $countRowHeader);
                    $countColumnHeader++;
                }

                // Freeze Header
                $workSheet->freezePaneByColumnAndRow(2, $countRowHeader + 1);

                // Content
                $countStaticColumn = $isSingleSurvey ? 2 : 1; 
                $firstColumnContent = $isSingleSurvey ? 'C' : 'B';
                $firstRowContent = $countRowHeader + 1;
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
                        
                        if ($averageInBottom && $iSession === count($this->sessions)-1) {
                            $event->sheet->setCellValue($column . $row, '=AVERAGE('.$column.$firstRowContent.':'.$column.($row-1).')');
                        }
                        
                        $cell = $event->sheet->getCell($column.$row);
                        
                        $column++;
                    }
                    
                    $lastSheetColumn = $column . ($firstRowContent + $iSession);

                    if ($totalInBottom || $averageInBottom) {
                        if ($totalInBottom && $iSession === count($this->sessions)-1)
                            $event->sheet->setCellValue($column . $row, '=SUM('.$column.$firstRowContent.':'.$column.($row-1).')');
                        if ($averageInBottom && $iSession === count($this->sessions)-1)
                            $event->sheet->setCellValue($column . $row, '=AVERAGE('.$column.$firstRowContent.':'.$column.($row-1).')');
                        if ($iSession === count($this->sessions)-1 && !$isSingleSurvey)
                            $event->sheet->setCellValue('A' . $row, 'Jumlah');
                        if ($iSession === count($this->sessions)-1 && $isSingleSurvey)
                            $event->sheet->setCellValue('B' . $row, 'Jumlah');
                    }

                    if ($totalInRight)
                        $event->sheet->setCellValue($lastSheetColumn, '=SUM('.$firstSheet.':'.$beforeLastSheetColumn.')');
                    if ($averageInRight)
                        $event->sheet->setCellValue($lastSheetColumn, '=AVERAGE('.$firstSheet.':'.$beforeLastSheetColumn.')');

                }

                $lastColumnContent = $row;
                $lastRowContent = $column;
                $lastSheetContent = $column . $row;

                // hyperlinks
                foreach ($urlCells as $urlCell) {
                    for ($i = $firstRowContent; $i < $lastColumnContent; $i++) { 
                        $cellValue = $event->sheet->getCell($urlCell.$i)->getValue();
                        if ($cellValue) {
                            //Call the new macro
                            $event->sheet->setURL(
                                $urlCell . $i,
                                $cellValue
                            );
                        }
                    }
                }

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
        $headerLevel3 = ['No.'];
        $headerLevel2 = ['No.'];
        $headerLevel1 = [' '];
        $questions = $this->survey->questions->toArray();
        $totalInRight = $this->survey->total_in_right;
        $averageInRight = $this->survey->average_in_right;

        foreach ($questions as $question) {
            if (in_array($question['type'], ['radio', 'checkbox'])) {
                foreach ($question['options'] as $iOption => $option) {
                    if ($iOption === 0) {
                        $headerLevel2[] = $question['alias'];
                    } else {
                        $headerLevel2[] = '';
                    }
                    
                    $headerLevel3[] = $option['value'];
                    $headerLevel1[] = $option['value'];
                }
            } else {
                $headerLevel3[] = $question['alias'];
                $headerLevel2[] = $this->isHeaderLevel3Exist ? ' ' : $question['alias'];
                // $headerLevel2[] = ' ';
                $headerLevel1[] = $question['alias'];
            }
        }

        if ($totalInRight) {
            $headerLevel3[] = 'Jumlah';
            $headerLevel2[] = 'Jumlah';
            $headerLevel1[] = 'Jumlah';
        }

        if ($averageInRight) {
            $headerLevel3[] = 'Rata Rata';
            $headerLevel2[] = 'Rata Rata';
            $headerLevel1[] = 'Rata Rata';
        }


        if ($this->isHeaderLevel3Exist)
            return [$headerLevel3, $headerLevel2, $headerLevel1];

        return [$headerLevel2, $headerLevel1];
    }

    public function map($data): array
    {
        $results = [];
        $questions = $this->survey->questions->toArray();
        $skipNumbering = false;
        
        foreach ($questions as $iQuestion => $question) {
                $responseUser = '';
                $responsesUser = [];
                $iResponse = 0;

                $options = array_column($question['options'], 'value');
                $responses = array_values(array_filter($question['responses'], function ($response) use ($data) {
                        return $response['survey_session_id'] === $data->id;
                    }));
                foreach ($responses as $response) {
                    // if ($data->id === $response['survey_session_id']) {
                        // detect type response static
                        if (in_array($response['note'], ['hidden', 'next point'])) {
                            $skipNumbering = true;
                        }

                        if (in_array($question['type'], ['radio', 'checkbox'])) {
                            foreach ($options as $iOption => $option) {
                                if ($response['content'] === $option) {
                                    $responseInRow = $question['is_content_option'] ? $response['content'] : 'V';
                                    $responsesUser[] = $responseInRow;
                                } elseif (!isset($responses[$iResponse + 1]) && $iOption >= $iResponse) {
                                // } elseif (!array_key_exists(($iResponse + 1), $responses) && $iOption >= $iResponse) {
                                    $responsesUser[] = '';
                                }
                            }
                        }

                        if ($question['type'] === 'file') {
                            $responseUser = $response['link'];
                        } else {
                            $responseUser = $response['content'];
                        }

                        $iResponse++;
                    // }
                }

                if (count($responsesUser)) {
                    array_push($results, ...$responsesUser);     
                } else {
                    $results[] = $responseUser;
                }
        }

        if ($skipNumbering) {
            $results = [' ', ...$results];
        } else {
            $results = [$this->row, ...$results];
            $this->row++;
        }

        return $results;
    }
    
}

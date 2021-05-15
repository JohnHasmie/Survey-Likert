<?php

namespace App\Actions\Export;

use App\Models\Survey;
use App\Models\User;

use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportSurvey implements FromCollection, WithStyles, WithMapping, WithEvents, WithTitle, ShouldAutoSize, WithHeadings
{
    use Exportable;

    protected $survey;
    protected $users;
    protected $row = 1;

    public function __construct($surveyId)
	{
        $this->survey = Survey::whereId($surveyId)->with(['questions.responses', 'questions.options'])->first();
        $this->users = User::with(['responses' => function ($q) {
                $q->groupBy('user_id');
                $q->groupBy('survey_id');
            }])
            ->whereHas('responses', function ($query) {
                $query->whereSurveyId($this->survey->id);
            })
            ->get();
	}

    public function collection()
    {
        return $this->users;
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
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'ffff00',
                ],
            ]
        ];

        $contentStyling = [];
        foreach ($this->users as $key => $value) {
            $contentStyling[$key+3] = $styleContent;
        }

        return [
            1 => $styleHeader,
            2 => $styleHeader,
            ...$contentStyling,
            // Styling a specific cell by coordinate.
            // 'B2' => ['font' => ['italic' => true]],

            // Styling an entire column.
            // 'C'  => ['font' => ['size' => 16]],
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

                $alphabet = 'B';
                foreach ($questions as $question) {
                    if (in_array($question['type'], ['radio', 'checkbox'])) {
                        $firstColumn = $alphabet . '1';
                        foreach ($question['options'] as $iOption => $option) {
                            if ($iOption !== 0) $alphabet++;
                        }
                        $lastColumn = $alphabet . '1';

                        $event->sheet->mergeCells($firstColumn . ':' . $lastColumn);
                    } else {
                        $event->sheet->mergeCells($alphabet . '1:' . $alphabet. '2'); 
                    }

                    $alphabet++;
                }

                $workSheet = $event->sheet->getDelegate();
                // $workSheet->freezePane('A3');
                $workSheet->freezePaneByColumnAndRow(2,3);
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
        $secondHeader = [''];
        $questions = $this->survey->questions->toArray();

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

        return [$firstHeader, $secondHeader];
    }

    public function map($data): array
    {
        $results = [$this->row];
        $questions = $this->survey->questions->toArray();

        foreach ($questions as $question) {
                $responseUser = '';
                $responsesUser = [];

                $options = array_column($question['options'], 'value');
                foreach ($question['responses'] as $response) {
                    if ($data->id === $response['user_id']) {
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

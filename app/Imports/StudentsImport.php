<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use App\Models\Student;

class StudentsImport implements ToModel
{
    use Importable;

    public $emails = [];
    protected $duplicate_emails = [];

    public function model(array $row)
    {
        // Ensure the row has at least the expected number of elements
        if (!isset($row[0], $row[1], $row[2], $row[3], $row[4])) {
            return null; // Skip this row if it doesn't have enough data
        }

        // Check if the email already exists in the emails array
        if (in_array($row[1], $this->emails)) {
            // Optionally, handle the duplicate email case here
            $this->duplicate_emails[] = $row[1];
            return null;
        } else {
            // If it's a new email, add it to the emails array
            $this->emails[] = $row[1];
        }
        return new Student([
            'name'     => $row[0],
            'email'    => $row[1],
            'password' => $row[2],
            'address'  => $row[3],
            'study_course' => $row[4]
        ]);
    }

    public function getDuplicates()
    {
        return $this->duplicate_emails;
    }

}
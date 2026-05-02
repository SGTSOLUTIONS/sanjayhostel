<?php

namespace App\Imports;

use App\Models\PropertiesMaster;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PropertiesImport implements ToModel, WithHeadingRow, WithChunkReading
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;

    public function model(array $row)
    {
        // Convert numeric values to strings where needed
        $wardNo = isset($row['ward_no']) ? (string) $row['ward_no'] : '';
        $newDoorNo = isset($row['new_door_no']) ? (string) $row['new_door_no'] : '';

        // Skip if required fields are missing
        if (empty($wardNo) || empty($row['assessment']) || empty($row['zone'])) {
            $this->skippedCount++;
            return null;
        }

        $existing = PropertiesMaster::where('assessment', $row['assessment'])
            ->where('zone', $row['zone'])
            ->where('ward_no', $wardNo)
            ->first();

        $data = [
            'assessment' => $row['assessment'],
            'zone' => $row['zone'],
            'ward_no' => $wardNo,
            'old_assessment' => $row['old_assessment'] ?? null,
            'road_name' => $row['road_name'] ?? '',
            'owner_name' => $row['owner_name'] ?? '',
            'old_door_no' => isset($row['old_door_no']) ? (string) $row['old_door_no'] : null,
            'new_door_no' => $newDoorNo,
            'phone_number' => isset($row['phone_number']) ? (string) $row['phone_number'] : null,
            'plot_area' => is_numeric($row['plot_area'] ?? 0) ? $row['plot_area'] : 0,
            'half_year_tax' => is_numeric($row['half_year_tax'] ?? 0) ? $row['half_year_tax'] : 0,
            'balance' => is_numeric($row['balance'] ?? 0) ? $row['balance'] : 0,
            'corporation' => $row['corporation'] ?? '',
        ];

        if ($existing) {
            $existing->update($data);
            $this->updatedCount++;
        } else {
            PropertiesMaster::create($data);
            $this->importedCount++;
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getStatistics()
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'total' => $this->importedCount + $this->updatedCount + $this->skippedCount
        ];
    }
}

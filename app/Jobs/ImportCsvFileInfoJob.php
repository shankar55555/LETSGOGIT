<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ExportLog;
use League\Csv\Reader;
use League\Csv\Statement;
use Modules\AlertAndNotification\Models\BToBUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportCsvFileInfoJob implements ShouldQueue
{
    use Queueable;
    protected $filePath, $logId, $createdBy;

    public function __construct($filePath, $logId, $createdBy)
    {
        $this->filePath = $filePath;
        $this->logId = $logId;
        $this->createdBy = $createdBy;
    }

    public function handle()
    {
        $csv = Reader::createFromPath(storage_path('app/' . $this->filePath), 'r');
        $csv->setHeaderOffset(0);
        $records = Statement::create()->process($csv);

        $imported = 0;
        $duplicates = 0;
        $notCreated = 0;
        $notCreatedList = collect();
        $duplicateList = collect();
        $table_name = ExportLog::where('id', $this->logId)->pluck('table_name')->first();

        foreach ($records as $record) {
            if ($table_name == "b_to_b_users") {
                try {
                    $name = trim($record['Name']);
                    $email = strtolower(trim($record['Email']));
                    $contactNo = trim($record['Contact No']);
                    $status = trim($record['Status'] ?? 'active');

                    $userExists = BToBUser::where([['name', $name], ['email', $email], ['contact_no', $contactNo],])->exists();
                    if ($userExists) {
                        $duplicates++;
                        $duplicateList->push($this->formatImportError($name, $email, $contactNo, "Duplicate entry — already exists"));
                        continue;
                    }

                    BToBUser::create([
                        'name'         => $name,
                        'company'      => $record['Company'] ?? null,
                        'role'         => $record['Role'] ?? null,
                        'country_code' => $record['Country Code'] ?? null,
                        'contact_no'   => $contactNo,
                        'email'        => $email,
                        'status'       => $status,
                        'address'      => $record['Address'] ?? null,
                        'created_by'   => $this->login_user->uuid ?? null,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    createExceptionError($e, 'Import Csv File Info Job', __FUNCTION__);
                    $notCreated++;
                    $notCreatedList->push($this->formatImportError($name, $email, $contactNo, $e->getMessage()));
                }
            }
        }

        # Save summary info into JSON column in export_logs
        ExportLog::where('id', $this->logId)->update([
            'body_params' => json_encode([
                'imported_count'   => $imported,
                'duplicate_count'  => $duplicates,
                'not_created_count' => $notCreated,
                'duplicate_list'   => $duplicateList,
                'not_created_list' => $notCreatedList,
            ]),
        ]);
    }

    public function formatImportError($name, $email, $contactNo, $message)
    {
        return [
            'name'        => $name,
            'email'       => $email,
            'contact_no'  => $contactNo,
            'message'     => $message,
        ];
    }
}

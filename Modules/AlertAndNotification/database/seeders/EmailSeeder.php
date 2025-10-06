<?php

namespace Modules\AlertAndNotification\Database\Seeders;

use App\Constants\CommonConst;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AlertAndNotification\Constants\EmailConst;
use Modules\AlertAndNotification\Models\NotificationCategory;
use Modules\AlertAndNotification\Models\NotificationTemplateSection;
use Modules\AlertAndNotification\Models\NotificationType;
use Modules\AlertAndNotification\Models\NotificationVariable;

class EmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NotificationCategory::query()->delete();
        NotificationType::query()->delete();
        NotificationTemplateSection::query()->delete();
        NotificationVariable::query()->delete();

        $email_List = CommonConst::ACCOUNT_EMAIL_LIST;
        $prams = ["name" => "EMAIL_TEMPLATE", "list" => $email_List, "position" => false];
        $email_List = readConstFileList(...$prams);

        // Count total entries for progress bar
        $total = collect($email_List)->sum(fn($cat) => count($cat['type']));
        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        foreach ($email_List as $categoryData) {
            $category = NotificationCategory::updateOrCreate(['category' => $categoryData['category']]);

            foreach ($categoryData['type'] as $type) {
                $emailType = NotificationType::updateOrCreate(
                    ['category_id' => $category->id, 'type_key' => $type['type_key']],
                    [
                        'title' => $type['title'],
                        'description' => $type['description'] ?? '',
                        'category_id' => $category->id,
                    ]
                );
                DB::beginTransaction();
                try {
                    NotificationTemplateSection::updateOrCreate(
                        ['notification_type_id' => $emailType->id],
                        [
                            "title" => $type['template']['title'],
                            'email_body' => $type['template']['email_body'],
                            'email_subject' => $type['template']['email_subject'],
                            'whats_app_message' => $type['template']['whats_app_message'] ?? null,
                            'sms_message' => $type['template']['sms_message'] ?? null,
                            'bell_notification_message' => $type['template']['bell_notification_message'] ?? null,
                            'app_message' => $type['template']['bell_notification_message'] ?? null,
                            'priority' => $type['template']['priority'],
                            'hidden_pre_header' => $type['template']['hidden_pre_header'],
                            'is_enable' => $type['template']['is_enable'],
                        ]
                    );

                    foreach ($type['variables'] as $variable) {
                        NotificationVariable::updateOrCreate([
                            'notification_type_id' => $emailType->id,
                            'variables' => $variable,
                        ]);
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->command->error($emailType->title . ' = ' . $e->getMessage());
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->command->info("\nEmail templates seeded successfully!");
    }
}

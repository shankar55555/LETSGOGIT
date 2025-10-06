<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class EnvUpdateCmd extends Command
{
    const GOOGLE_REDIRECT_URI = "";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'env:up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update or Add ENV values via interactive prompt';

    # Predefined keys and their default values
    protected $envKeys = [
        'MAIL_MAILER'        => 'smtp',
        'MAIL_HOST'          => 'smtp.gmail.com',
        'MAIL_PORT'          => '587',
        'MAIL_USERNAME'      => 'smtpeligocs@gmail.com',
        'MAIL_PASSWORD'      => 'cljpupohjxnutnxm',
        'MAIL_ENCRYPTION'    => 'tls',
        'MAIL_FROM_ADDRESS'  => 'smtpeligocs@gmail.com',
        'MAIL_FROM_NAME'     =>  '',
        "AISENSY_API_KEY" => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY3OGI2ZDI3MGM4MmNiMGJmY2JiZDcwZCIsIm5hbWUiOiJOT0JMRSBQT1dFUiBTT0xVVElPTlMiLCJhcHBOYW1lIjoiQWlTZW5zeSIsImNsaWVudElkIjoiNjc4YjZkMjcwYzgyY2IwYmZjYmJkNzA4IiwiYWN0aXZlUGxhbiI6IkZSRUVfRk9SRVZFUiIsImlhdCI6MTczNzE5MDY5NX0.JC1wNwV51gNySK43d08XmLi7r020I2UO8VV2M6gXmRY',
        "AISENSY_CAMPAIGN_NAME" => "test_marketing_campaign",
        "AISENSY_DOCUMENT_CAMPAIGN_NAME" => "test_marketing_campaign_document",
        "AISENSY_IMAGE_CAMPAIGN_NAME" => "test_marketing_campaign_image",
        "QUEUE_CONNECTION" => "database",
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('ENV Update Command Starting...');

        $editor = DotenvEditor::load();

        if (true || $this->confirm('Do you want to update all predefined ENV keys at once?')) {
            # Bulk update
            foreach ($this->envKeys as $key => $value) {
                if ($key === 'MAIL_FROM_NAME') {
                    $value = config('app.name');
                    $editor->setKey($key,  $value);
                } else {
                    $editor->setKey($key, $value);
                }

                $this->line("Set $key = $value");
            }
        } else {
            # Interactive update
            foreach ($this->envKeys as $key => $defaultValue) {
                $currentValue = env($key, 'Not Set');
                $this->line("Current value of $key: $currentValue");

                if (true || $this->confirm("Do you want to change value of $key?")) {
                    $newValue = $this->ask("Enter new value for $key", $currentValue);

                    # Apply special rule for MAIL_FROM_NAME
                    if ($key === 'MAIL_FROM_NAME') {
                        $editor->setKey($key, $newValue, true);
                        $this->line("Updated $key to \"{$newValue}\"");
                    } else {
                        $editor->setKey($key, $newValue);
                        $this->line("Updated $key to $newValue");
                    }
                }
            }
        }

        $editor->save();
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        $this->info('ENV Updated Successfully');

        return Command::SUCCESS;
    }
}

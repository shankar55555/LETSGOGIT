<?php

namespace Modules\AlertAndNotification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Rule slug to determine which notification rule to apply.
     *
     * @var string|null
     */
    protected ?string $rule_slug = null;

    /**
     * Data to be passed to the notification handler.
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Optional rule ID for filtering or referencing.
     *
     * @var string|null
     */
    protected ?string $rule_id = null;
    protected ?string $user_id = null;

    /**
     * Create a new job instance.
     *
     * @param string|null $ruleSlug
     * @param array $data
     * @param string|null $rule_id
     * @param string|null $user_id
     */
    public function __construct(?string $ruleSlug = null, array $data, ?string $rule_id = null, ?string $user_id = null)
    {
        $this->data = $data;
        $this->rule_slug = $ruleSlug;
        $this->rule_id = $rule_id;
        $this->user_id = $user_id ?? loginUserId();

        i("NotificationJob : initialized with rule_slug: {$ruleSlug}, rule_id: {$rule_id}");
        dLog('NotificationJob : data payload: ' . json_encode($data));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        i("NotificationJob : handle() started for rule_slug: {$this->rule_slug}");

        try {
            $notificationHelper = new NotificationHelper();
            $notificationHelper->handle($this->rule_slug, $this->data, $this->rule_id, $this->user_id);

            i("NotificationJob : completed successfully for rule_slug: {$this->rule_slug}");
        } catch (\Exception $e) {
            er("NotificationJob : failed: " . $e->getMessage());

            logWithContext('error', 'NotificationJob : execution error', [
                'rule_slug' => $this->rule_slug,
                'rule_id' => $this->rule_id,
                'data' => $this->data,
                'exception' => $e->getMessage()
            ]);
        }
    }
}

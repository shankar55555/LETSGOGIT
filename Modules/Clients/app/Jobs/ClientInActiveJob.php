<?php

namespace Modules\Clients\Jobs;

use App\Constants\CommonConst;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\AlertAndNotification\Helpers\NotificationHelper;
use Modules\Clients\Constants\ClientConst;
use Modules\Clients\Models\Client;
use Modules\AlertAndNotification\Helpers\RuleCheckHelper;

class ClientInActiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}
    /**
     * Processes clients with NO_ACTION status and triggers notifications based on matching rules
     *
     * This job retrieves clients with NO_ACTION status, checks them against predefined rules,
     * and sends notifications for matching clients using the NotificationHelper.
     *
     * @return void
     * @throws \Exception If the job encounters a critical error during execution
     */
    public function handle(): void
    {
        try {
            # Log job start
            i('ClientInActiveJob : started');

            # Retrieve rules for clients with NO_ACTION status
            $ruleCheckHelper = new RuleCheckHelper();
            $rules = $ruleCheckHelper->commonStatusConditionRules(CommonConst::MODULE_CLIENT, CommonConst::IN_ACTIVE, ClientConst::RULE_CLIENT_INACTIVE);

            # Exit if no rules are found
            if (empty($rules)) {
                i('ClientInActiveJob : No matching rules found for RULE_CLIENT_INACTIVE status');
                return;
            }

            # Fetch client IDs with NO_ACTION status
            $clientIds = Client::query()->where('status', CommonConst::IN_ACTIVE)->pluck('id')->toArray();

            # Exit if no clients are found
            if (empty($clientIds)) {
                i('ClientInActiveJob : No clients found with IN_ACTIVE status');
                return;
            }

            # Initialize notification helper
            $notificationHelper = new NotificationHelper();

            # Process each rule and its matching clients
            foreach ($rules as $rule) {
                # Get client IDs that match the current rule
                $matchedIds = Client::getMatchingIdsFromRule($rule, $clientIds);

                # Process each matching client
                foreach ($matchedIds as $clientId) {
                    try {
                        # Send notification for the client
                        $ruleSlug = null;
                        $notificationHelper->handle($ruleSlug, leadRuleNotification($clientId), $rule->id, loginUserId());
                        i("ClientInActiveJob : Notification sent successfully for client ID: {$clientId}");
                    } catch (\Exception $e) {
                        # Log notification failure
                        er("ClientInActiveJob : Failed to send notification for client ID: {$clientId}. Error: {$e->getMessage()}");
                    }
                }
            }

            # Log job completion
            i('ClientInActiveJob : completed successfully');
        } catch (\Exception $e) {
            # Log job failure
            er("ClientInActiveJob : failed. Error: {$e->getMessage()}");
            throw $e; # Re-throw to allow job retry if configured
        }
    }
}

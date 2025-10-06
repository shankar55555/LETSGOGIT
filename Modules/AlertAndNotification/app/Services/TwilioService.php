<?php

namespace Modules\AlertAndNotification\Services;

use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\RestException;

class TwilioService
{
    protected $twilio;

    public function __construct()
    {
        $sid    = config('services.twilio.sid');
        $token  = config('services.twilio.token');

        if (empty($sid) || empty($token)) {
            throw new \Exception('Twilio SID or Token not configured properly.');
        }

        $this->twilio = new TwilioClient($sid, $token);
    }

    public function sendTestSmsMediaMessage($to, $message)
    {
        try {
            $from = config('services.twilio.from');

            $response = $this->twilio->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            return (object)[
                'status' => true,
                'sid' => $response->sid,
                'to' => $response->to,
                'from' => $response->from,
                'message' => 'SMS sent successfully',
            ];
        } catch (RestException $e) {
            return (object)[
                'status' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            return (object)[
                'status' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    public function sendSmsMediaMessage($to, $message)
    {
        try {
            $from = config('services.twilio.from');

            $response = $this->twilio->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            return (object)[
                'status' => true,
                'sid' => $response->sid,
                'to' => $response->to,
                'from' => $response->from,
                'message' => 'SMS sent successfully',
            ];
        } catch (RestException $e) {
            return (object)[
                'status' => false,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            return (object)[
                'status' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }
}

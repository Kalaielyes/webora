<?php

require_once __DIR__ . '/config.php';

class Meeting
{
    public static function create(array $data): int
    {
        $sql = "INSERT INTO meeting_schedule (
                    organiser_id,
                    invited_emails,
                    meeting_date,
                    meeting_time,
                    message,
                    meeting_link,
                    provider
                ) VALUES (
                    :organiser_id,
                    :invited_emails,
                    :meeting_date,
                    :meeting_time,
                    :message,
                    :meeting_link,
                    :provider
                )";

        $stmt = Config::getConnexion()->prepare($sql);
        $stmt->execute([
            'organiser_id' => $data['organiser_id'],
            'invited_emails' => $data['invited_emails'],
            'meeting_date' => $data['meeting_date'],
            'meeting_time' => $data['meeting_time'],
            'message' => $data['message'],
            'meeting_link' => $data['meeting_link'],
            'provider' => $data['provider'],
        ]);

        return (int)Config::getConnexion()->lastInsertId();
    }
}

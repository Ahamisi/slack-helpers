<?php

namespace App\Http\Controllers;

use App\SlackClient;
use App\SlackMessage;
use App\SlackUser;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Wgmv\SlackApi\Facades\SlackChannel;
use Wgmv\SlackApi\Facades\SlackUser as SlackUserClient;

class SlashIsIn extends Controller
{
    protected $replyMessage;

    public function __construct($teamId = null)
    {
        $this->channelId = config('services.slack.general_channel_id');
        $this->teamId = $teamId ?? config('services.slack.team_id');
        $this->client = new SlackClient($this->teamId);
        $this->users = $this->client->getUsers();
        $this->replyMessage = new SlackMessage;
    }

    public function __invoke(Request $request)
    {
        $message = $request->get('text');
        $userId = $request->get('user_id');

        // Figure out who was @mentioned in the slash command
        // Slack escapes @mentions to look like <@U012ABCDEF>
        $pattern = "/\<@([\A-Z0-9]+)(?:\|[\w]+)?\>/";
        preg_match_all($pattern, $message, $mentions);

        if (count($mentions[1]) > 1) {
            return $this->reply("Only mention one person, so I know who you're looking for! E.g. */IsIn @someone*");
        }

        // Get the list of who's in
        $statusData = (new GetStaffIn())->getStatuses();

        if (array_get($statusData, 'status') != 'success') {
            return $this->reply(
                "Sorry, something went wrong trying to look that up. Here's the error message:\n> "
                . array_get($statusData, 'message', '(No error message)')
            );
        }

        $statuses = collect(array_get($statusData, 'data.statuses'));

        if (empty($mentions[0])) {
            // If no one was @mentioned, return all users that are @in (and specify those on break)
            $statusText = $statuses->map(function ($status) {
                $emoji = [
                    'in' => '1:wave:',
                    'out' => '4:v:',
                    'lunch' => '3:bento:',
                    'break' => '2:coffee:',
                ];

                return "{$emoji[$status['status']]} *@{$status['display_name']}";
            })
            ->values()
            ->sort()
            ->map(function ($status) {
                return substr($status, 1);
            });

            return $this->reply($statusText->implode("\n"));
        }

        // Get the status of the mentioned person
        if ($info = $statuses->get($mentions[1][0])) {
            return $this->reply("@{$info['display_name']} is *@{$info['status']}*. Their last message in #general was {$info['since']}:\n> {$info['last_message']}");
        }

        return $this->reply("I've not seen @{$info['display_name']} in #general yet today, so you can assume they're *@out* right now.");
    }

    protected function reply($text)
    {
        return $this->replyMessage
            ->text($text)
            ->toString();
    }
}

<?php

namespace App\Controllers;

class Admin extends BaseController
{
    public function index() {
        $client = \Config\Services::curlrequest([
            'baseURI' => getenv('SVC_USER'),
            'headers' => [
                'User-Agent' => 'CodeIgniter-App'
            ]
        ]);

        try {
            $response = $client->get('');
            $leaderboard= json_decode($response->getBody(), true);

            usort($leaderboard, function($a, $b) {
                $a = (object)$a;
                $b = (object)$b;
                $aStars = $a->gameStars + $a->reviewStars;
                $bStars = $b->gameStars + $b->reviewStars;
                if ($aStars == $bStars) return 0;
                return ($aStars < $bStars) ? 1 : -1;
            });

            $data['leaderboard'] = $leaderboard;

        } catch (\Exception $e) {
            // Log the error
            log_message('error', '[Leaderboard] ' . $e->getMessage());
            $data['error'] = 'Could not fetch leaderboard data. Please check the logs.';
        }

        return view('admin_leaderboard', $data);
    }
}

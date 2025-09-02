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
            $data['leaderboard'] = json_decode($response->getBody(), true);

            usort($data, function($a, $b) {
                return ($b->gameStars + $b->reviewStars) - ($a->gameStars + $a->reviewStars);
            });
            print_r($data[0][0]);

        } catch (\Exception $e) {
            // Log the error
            log_message('error', '[Leaderboard] ' . $e->getMessage());
            $data['error'] = 'Could not fetch leaderboard data. Please check the logs.';
        }

        return view('admin_leaderboard', $data[0]);
    }
}

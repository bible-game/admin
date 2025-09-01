<?php

namespace App\Controllers;

class Admin extends BaseController
{
    public function index()
    {
        $client = \Config\Services::curlrequest([
            'baseURI' => getenv('SVC_USER'),
            'headers' => [
                'User-Agent' => 'CodeIgniter-App'
            ]
        ]);

        try {
            $response = $client->get('');
            $data['leaderboard'] = json_decode($response->getBody(), true);
            log_message('info', $response->getBody(), []);
        } catch (\Exception $e) {
            // Log the error
            log_message('error', '[Leaderboard] ' . $e->getMessage());
            $data['error'] = 'Could not fetch leaderboard data. Please check the logs.';
        }

        return view('admin_leaderboard', $data);
    }
}

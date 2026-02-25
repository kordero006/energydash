<?php
namespace App\Models;

class Reading {
    private $url = "https://afvthfxrwmkkepzqvoua.supabase.co/rest/v1/readings";
    private $key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFmdnRoZnhyd21ra2VwenF2b3VhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzEyNzgzOTksImV4cCI6MjA4Njg1NDM5OX0.Aidqkv6AHDZ0-Oc0CQSrILfeL2JThEZMkh6mWYXJdHc";

    public function getData($filter = 'today', $limit = 10, $offset = 0) {
        $startDate = match($filter) {
            'week'  => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            'all'   => '2020-01-01',
            default => date('Y-m-d'),
        };

        $queryUrl = $this->url . "?created_at=gte." . $startDate . "T00:00:00&order=created_at.desc&limit=$limit&offset=$offset";

        $ch = curl_init($queryUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
<?php
    $BASE_URL = "http://query.yahooapis.com/v1/public/yql";
    $yql_query = 'select item.condition.text, item.condition.temp from weather.forecast where woeid in (select woeid from geo.places(1) where text="schladen, ni, de")';
    $yql_query_url = $BASE_URL . "?q=" . urlencode($yql_query) . "&format=json&u=c";
    // Make call with cURL
    $session = curl_init($yql_query_url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER,true);
    $json = curl_exec($session);
    // Convert JSON to PHP object
     $phpObj =  json_decode($json);
    echo $phpObj->query->results->channel->item->condition->text .  "\n";
    echo round(($phpObj->query->results->channel->item->condition->temp -32) / 1.8 ).  "\n";
?>

<?php

$result = '';

if (isset($_GET['action'])) {

    if ("get" == $_GET['action']) {
        $url = "http://localhost/miniPhpRest/api/v1/users";

        $client = curl_init($url);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_HEADER  , true);
        $response = curl_exec($client);

        $header_size = curl_getinfo($client, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $httpcode = curl_getinfo($client, CURLINFO_HTTP_CODE);
        $result = json_decode($body);
        if (empty($result)) {
            $result = $body;
        }
        curl_close($client);

    }

}


?>
<!DOCTYPE>
<html>
<head>
    <title>Test API</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>

<div class="container">
    <h1>Test API</h1>

    <div class="row">
        <div class="col-md-6 mb-3">
            <h2>Test Get users</h2>
            <code>/api/v1/users</code>
            <a href="test_Api.php?action=get" class="btn btn-primary">Get Users</a>
        </div>


        <div class="col-md-6 mb-3">
            <form action="test_Api.php" method="post">
                <input type="text" name="name" placeholder="Name">
                <input type="text" name="email" placeholder="Email">
                <input type="submit" name="submit" value="Submit">
            </form>
        </div>

    </div>

    <div class="row">
        <div class="col-12">
            <pre><?php
                print_r($result); ?></pre>
        </div>
    </div>
</body>
</html>
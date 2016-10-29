<?php
include_once('vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Httpful\Exception\ConnectionErrorException;

try {
    $config = Yaml::parse(file_get_contents('config.yml'));
} catch (ParseException $e) {
    http_response_code(500);
    echo json_encode(array(
        'code' => 500,
        'msg' => 'Unable to parse the YAML string',
    ));
    exit;
}

if (!isset($_POST['id'])
    || !isset($_POST['title'])
    || !isset($_POST['due'])
    || !isset($_POST['description'])
    || !isset($_POST['signature'])
) {
    http_response_code(403);
    echo json_encode(array(
        'code' => 403,
        'msg' => 'Param missing',
    ));
    exit;
}

$id = $_POST['id'];
$title = $_POST['title'];
$due = $_POST['due'];
$desc = $_POST['description'];
$signature = $_POST['signature'];

$truesignature = hash('sha256', $id . $config['secret']);

if ($truesignature != $signature) {
    http_response_code(401);
    echo json_encode(array(
        'code' => 401,
        'msg' => 'Bad signature',
    ));
    exit;
}

$message = "$title\nPour le $due\n-------\n$desc";

$message = preg_replace("/\r|\n/", '%0A', $message);

try {
    $response = \Httpful\Request::get($config['address'] . $message)->send();
    echo json_encode(array(
        'code' => $response->code,
        'msg' => 'done',
    ));
} catch (ConnectionErrorException $e) {
    http_response_code(500);
    echo json_encode(array(
        'code' => 500,
        'msg' => 'Unable to access internet',
    ));
    exit;
}
?>

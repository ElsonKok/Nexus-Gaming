<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);
header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Extract path segments after /cos30043/s106200220/Project/resources/apis.php
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $pathInfo = '';
    if (strpos($uriPath, $scriptName) === 0) {
        $pathInfo = trim(substr($uriPath, strlen($scriptName)), '/');
    }

    $request = $pathInfo !== '' ? explode('/', $pathInfo) : array();
    $input = json_decode(file_get_contents('php://input'), true);

    // Mercury database connection
    $conn = mysqli_connect('feenix-mariadb.swin.edu.au', 's106200220', '280904', 's106200220_db');
    //$conn = mysqli_connect('localhost', 'root', '', 'nexus');

    if (!$conn) {
        http_response_code(500);
        echo json_encode(array('error' => 'Database connection failed: ' . mysqli_connect_error()));
        exit;
    }

    mysqli_set_charset($conn, 'utf8mb4');

    // Whitelist allowed tables
    $allowedTables = array('game', 'cart', 'developer', 'favourite', 'payment', 'user', 'review');

    // First segment is table name; default to 'game' if omitted
    $firstSegment = isset($request[0]) ? preg_replace('/[^a-z0-9_]+/i', '', $request[0]) : '';

    if (in_array($firstSegment, $allowedTables, true)) {
        $table = array_shift($request);
    } else {
        $table = 'game';
    }

    // Auto-generate next sequential ID on POST if missing or empty
    $idConfigs = array(
        'game'      => array('col' => 'game_id',      'prefix' => 'G'),
        'user'      => array('col' => 'user_id',      'prefix' => 'U'),
        'developer' => array('col' => 'developer_id', 'prefix' => 'D'),
        'cart'      => array('col' => 'cart_id',      'prefix' => 'C'),
        'payment'   => array('col' => 'payment_id',   'prefix' => 'P'),
        'favourite' => array('col' => 'fav_id',       'prefix' => 'F'),
        'review'    => array('col' => 'review_id',    'prefix' => 'R')
    );

    if ($method === 'POST' && isset($idConfigs[$table])) {
        if (!is_array($input)) {
            $input = array();
        }

        $idCol = $idConfigs[$table]['col'];
        $prefix = $idConfigs[$table]['prefix'];

        if (empty($input[$idCol])) {
            $maxRes = mysqli_query(
                $conn,
                "SELECT MAX(CAST(SUBSTRING(`$idCol`, 2) AS UNSIGNED)) AS max_num FROM `$table` WHERE `$idCol` LIKE '{$prefix}%'"
            );
            $maxRow = $maxRes ? mysqli_fetch_assoc($maxRes) : null;
            $nextNum = ($maxRow && $maxRow['max_num'] !== null) ? ((int)$maxRow['max_num'] + 1) : 1;

            $input[$idCol] = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }

        if ($table === 'user' && !isset($input['avatar'])) {
            $input['avatar'] = '';
        }
        if ($table === 'cart' && !isset($input['quantity'])) {
            $input['quantity'] = 1;
        }
    }

    // Convert Base64 data URIs into raw binary bytes for BLOB columns (image & avatar)
    if (is_array($input)) {
        foreach (array('image', 'avatar') as $blobCol) {
            if (isset($input[$blobCol]) && is_string($input[$blobCol]) && $input[$blobCol] !== '') {
                if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.*)$/s', $input[$blobCol], $matches)) {
                    $decoded = base64_decode($matches[1]);
                    if ($decoded !== false) {
                        $input[$blobCol] = $decoded;
                    }
                }
            }
        }
    }

    // Remaining segments: optional search field and value
    $fld = preg_replace('/[^a-z0-9_]+/i', '', (string) array_shift($request));
    $key = array_shift($request);
    $escapedKey = $key !== null ? mysqli_real_escape_string($conn, (string) urldecode($key)) : null;

    // Prevent duplicate emails across BOTH `user` and `developer` tables on POST / PUT
    if (($method === 'POST' || $method === 'PUT') && in_array($table, array('user', 'developer'), true) && is_array($input) && !empty($input['email'])) {
        $cleanEmail = strtolower(trim((string) $input['email']));
        $input['email'] = $cleanEmail;
        $escEmail = mysqli_real_escape_string($conn, $cleanEmail);

        $excludeUser = ($method === 'PUT' && $table === 'user' && $fld === 'user_id' && $escapedKey !== null)
            ? " AND `user_id` != '$escapedKey'"
            : '';
        $excludeDev = ($method === 'PUT' && $table === 'developer' && $fld === 'developer_id' && $escapedKey !== null)
            ? " AND `developer_id` != '$escapedKey'"
            : '';

        $uCheck = mysqli_query($conn, "SELECT 1 FROM `user` WHERE LOWER(TRIM(`email`)) = '$escEmail'$excludeUser LIMIT 1");
        $dCheck = mysqli_query($conn, "SELECT 1 FROM `developer` WHERE LOWER(TRIM(`email`)) = '$escEmail'$excludeDev LIMIT 1");

        if (($uCheck && mysqli_num_rows($uCheck) > 0) || ($dCheck && mysqli_num_rows($dCheck) > 0)) {
            http_response_code(409);
            echo json_encode(array('error' => 'This email is already registered as a Player or Creator.'));
            mysqli_close($conn);
            exit;
        }
    }

    // Prepare SET clause for PUT / POST
    $set = '';
    if (is_array($input) && count($input) > 0) {
        $columns = preg_replace('/[^a-z0-9_]+/i', '', array_keys($input));
        $values = array_map(function ($value) use ($conn) {
            if ($value === null) return null;
            if (is_array($value)) {
                $value = json_encode($value);
            }
            return mysqli_real_escape_string($conn, (string) $value);
        }, array_values($input));

        $parts = array();
        for ($i = 0; $i < count($columns); $i++) {
            if ($columns[$i] === '') continue;
            $valSql = $values[$i] === null ? 'NULL' : '"' . $values[$i] . '"';
            $parts[] = '`' . $columns[$i] . '`=' . $valSql;
        }
        $set = implode(',', $parts);
    }

    // Build SQL query
    switch ($method) {
        case 'GET':
            $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : '';
            $sql = "SELECT * FROM `$table`" . $where;
            break;
        case 'PUT':
            $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : ' WHERE 0=1';
            $sql = "UPDATE `$table` SET $set" . $where;
            break;
        case 'POST':
            $sql = "INSERT INTO `$table` SET $set";
            break;
        case 'DELETE':
            $where = ($fld !== '' && $escapedKey !== null) ? " WHERE `$fld`='$escapedKey'" : ' WHERE 0=1';
            $sql = "DELETE FROM `$table`" . $where;
            break;
        default:
            http_response_code(405);
            echo json_encode(array('error' => 'Method not allowed'));
            mysqli_close($conn);
            exit;
    }

    $result = mysqli_query($conn, $sql);

    if ($result) {
        if ($method === 'GET') {
            $rows = array();
            while ($row = mysqli_fetch_assoc($result)) {
                foreach (array('image', 'avatar') as $blobCol) {
                    if (isset($row[$blobCol]) && $row[$blobCol] !== null && $row[$blobCol] !== '') {
                        if (!preg_match('/^(https?:\/\/|data:image\/)/i', $row[$blobCol])) {
                            $mime = 'image/jpeg';
                            if (function_exists('finfo_buffer')) {
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $detected = $finfo ? finfo_buffer($finfo, $row[$blobCol]) : false;
                                if ($finfo) finfo_close($finfo);
                                if ($detected && strpos($detected, 'image/') === 0) {
                                    $mime = $detected;
                                }
                            }
                            $row[$blobCol] = 'data:' . $mime . ';base64,' . base64_encode($row[$blobCol]);
                        }
                    }
                }
                $rows[] = $row;
            }
            echo json_encode($rows);
        } elseif ($method === 'POST') {
            $response = array('insert_id' => mysqli_insert_id($conn));
            if (isset($idConfigs[$table])) {
                $idCol = $idConfigs[$table]['col'];
                if (isset($input[$idCol])) {
                    $response[$idCol] = $input[$idCol];
                }
            }
            echo json_encode($response);
        } else {
            echo json_encode(array('affected_rows' => mysqli_affected_rows($conn)));
        }
    } else {
        http_response_code(400);
        echo json_encode(array('error' => mysqli_error($conn)));
    }

    mysqli_close($conn);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
?>